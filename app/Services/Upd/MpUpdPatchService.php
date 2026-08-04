<?php

namespace App\Services\Upd;

use App\Invoice;
use App\InvoiceLine;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Http\UploadedFile;

/**
 * Патч УПД-XML маркетплейса (Озон/ВБ) под наш счёт.
 *
 * Файл маркетплейса не пересобирается — правится точечно:
 *   - НомерДок (и РеквНомерДок подтверждения) → префикс МП + номер счёта
 *     (русская «О» для Озона, «В» для ВБ — по ИНН покупателя);
 *   - Подписант → Верхотуров (как в нашем шаблоне УПД);
 *   - в ДопСведТов строк добавляются КИЗ привязанных к счёту кодов;
 *   - в ИдФайл заглушки Озона заменяются на ЭДО-идентификаторы сторон,
 *     а при наличии КИЗ выставляется признак маркированных товаров
 *     (2-я группа хвоста 5.03); имя файла = ИдФайл.xml.
 * Даты и вся остальная структура файла сохраняются как есть,
 * результат — в исходной кодировке windows-1251.
 */
class MpUpdPatchService
{
    private const MP_PREFIX = [
        '7704217370' => 'О', // Озон («Интернет Решения»)
        '7721546864' => 'В', // Вайлдберриз
    ];

    /**
     * @return array{xml: string, filename: string, warnings: string[]}
     */
    public function patch(Invoice $invoice, UploadedFile $file): array
    {
        $dom = new DOMDocument();
        if (!@$dom->loadXML($file->get(), LIBXML_NONET)) {
            abort(422, 'Файл не читается как XML');
        }
        $xp = new DOMXPath($dom);

        /** @var DOMElement|null $svFact */
        $svFact = $xp->query('//СвСчФакт')->item(0);
        if (!$svFact) {
            abort(422, 'В файле нет СвСчФакт — это не УПД');
        }

        $buyerInn = $xp->evaluate('string(//СвСчФакт/СвПокуп/ИдСв/СвЮЛУч/@ИННЮЛ)');
        $prefix = self::MP_PREFIX[$buyerInn] ?? null;
        if ($prefix === null) {
            abort(422, "Покупатель с ИНН {$buyerInn} — не Озон и не ВБ");
        }

        $newNumber = $prefix . $invoice->NS;
        $svFact->setAttribute('НомерДок', $newNumber);
        /** @var DOMElement $dok */
        foreach ($xp->query('//СвСчФакт/ДокПодтвОтгрНом') as $dok) {
            $dok->setAttribute('РеквНомерДок', $newNumber);
        }

        /** @var DOMElement $signer */
        foreach ($xp->query('//Документ/Подписант') as $signer) {
            $signer->setAttribute('Должн', 'ДИРЕКТОР');
            /** @var DOMElement $fio */
            foreach ($xp->query('./ФИО', $signer) as $fio) {
                $fio->setAttribute('Фамилия', 'Верхотуров');
                $fio->setAttribute('Имя', 'Михаил');
                $fio->setAttribute('Отчество', 'Сергеевич');
            }
        }

        $warnings = $this->injectMarkCodes($dom, $xp, $invoice);
        $fileId = $this->patchFileId($dom, $xp, $invoice, $warnings);

        return [
            'xml' => $dom->saveXML(),
            'filename' => $fileId . '.xml',
            'warnings' => $warnings,
        ];
    }

    /**
     * ИдФайл: заглушки Озона → ЭДО-идентификаторы сторон (как в InvoiceUpdSource),
     * при наличии КИЗ — признак маркировки в хвосте 5.03
     * (_<прослеживаемость>_<маркировка>_<алкоголь>_<табак>_<нефтепродукты>_<резерв>).
     *
     * @param string[] $warnings
     */
    private function patchFileId(DOMDocument $dom, DOMXPath $xp, Invoice $invoice, array &$warnings): string
    {
        $fileEl = $dom->documentElement;
        $fileId = $fileEl->getAttribute('ИдФайл');

        $fileId = strtr($fileId, [
            'ИдентификаторПолучателя' => $invoice->buyer->advancedBuyer->edo_id ?? $invoice->buyer->Inn,
            'ИдентификаторОтправителя' => $invoice->firm->EDOID,
        ]);

        if ($xp->query('//ТаблСчФакт/СведТов/ДопСведТов/НомСредИдентТов')->length > 0) {
            $fileId = preg_replace('/_(\d)_\d(_\d_\d_\d_\d{2})$/u', '_$1_1$2', $fileId, 1, $count);
            if ($count === 0) {
                $warnings[] = 'В ИдФайл не найден хвост признаков 5.03 — признак маркировки не выставлен';
            }
        }

        $fileEl->setAttribute('ИдФайл', $fileId);

        return $fileId;
    }

    /**
     * КИЗ по строкам: матчинг только по КодТов (наш GOODSCODE в файле МП).
     * Товар без кодов остаётся без НомСредИдентТов; расхождение штук — warning.
     *
     * @return string[]
     */
    private function injectMarkCodes(DOMDocument $dom, DOMXPath $xp, Invoice $invoice): array
    {
        $lines = InvoiceLine::with('markCodes')
            ->where('SCODE', $invoice->SCODE)
            ->get()
            ->groupBy('GOODSCODE');

        $warnings = [];
        /** @var DOMElement $tov */
        foreach ($xp->query('//ТаблСчФакт/СведТов') as $tov) {
            $num = $tov->getAttribute('НомСтр');
            /** @var DOMElement|null $dop */
            $dop = $xp->query('./ДопСведТов', $tov)->item(0);
            $goodsCode = $dop ? trim($dop->getAttribute('КодТов')) : '';
            if ($goodsCode === '') {
                abort(422, "Строка {$num}: в файле нет КодТов — не сматчить со счётом");
            }
            $group = $lines->get((int)$goodsCode);
            if (!$group) {
                abort(422, "Строка {$num}: товара {$goodsCode} нет в счёте №{$invoice->NS}");
            }

            $codes = $group->flatMap->markCodes;
            if ($codes->isEmpty()) {
                continue;
            }

            foreach ($xp->query('./НомСредИдентТов', $dop) as $stale) {
                $dop->removeChild($stale);
            }
            $nsit = $dom->createElement('НомСредИдентТов');
            foreach ($codes as $mc) {
                $kiz = $dom->createElement('КИЗ');
                $kiz->appendChild($dom->createTextNode($mc->KI));
                $nsit->appendChild($kiz);
            }
            $dop->appendChild($nsit);

            $pieces = (float)$codes->sum('QUANTITY');
            $kolTov = (float)$tov->getAttribute('КолТов');
            if ($pieces !== $kolTov) {
                $warnings[] = sprintf(
                    'Строка %s: в кодах %s шт, в УПД КолТов %s',
                    $num, rtrim(rtrim(number_format($pieces, 2, '.', ''), '0'), '.'),
                    $tov->getAttribute('КолТов')
                );
            }
        }

        return $warnings;
    }
}
