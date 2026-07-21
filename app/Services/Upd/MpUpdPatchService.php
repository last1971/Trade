<?php

namespace App\Services\Upd;

use App\Invoice;
use App\InvoiceLine;
use DOMDocument;
use DOMXPath;
use Illuminate\Http\UploadedFile;

/**
 * Патч УПД-XML маркетплейса (Озон/ВБ) под наш счёт.
 *
 * Файл маркетплейса не пересобирается — правится точечно:
 *   - НомерДок (и РеквНомерДок подтверждения) → префикс МП + номер счёта
 *     (русская «О» для Озона, «В» для ВБ — по ИНН покупателя);
 *   - Подписант → Верхотуров (как в нашем шаблоне УПД);
 *   - в ДопСведТов строк добавляются КИЗ привязанных к счёту кодов.
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

        $svFact = $xp->query('//СвСчФакт')->item(0);
        if (!$svFact) {
            abort(422, 'В файле нет СвСчФакт — это не УПД');
        }

        $buyerInn = $xp->evaluate('string(//СвСчФакт/СвПокуп/ИдСв/СвЮЛУч/@ИННЮЛ)');
        $prefix = self::MP_PREFIX[$buyerInn] ?? null;
        if ($prefix === null) {
            abort(422, "Покупатель с ИНН {$buyerInn} — не Озон и не ВБ");
        }

        $oldNumber = $svFact->getAttribute('НомерДок');
        $newNumber = $prefix . $invoice->NS;
        $svFact->setAttribute('НомерДок', $newNumber);
        foreach ($xp->query('//СвСчФакт/ДокПодтвОтгрНом') as $dok) {
            $dok->setAttribute('РеквНомерДок', $newNumber);
        }

        foreach ($xp->query('//Документ/Подписант') as $signer) {
            $signer->setAttribute('Должн', 'ДИРЕКТОР');
            foreach ($xp->query('./ФИО', $signer) as $fio) {
                $fio->setAttribute('Фамилия', 'Верхотуров');
                $fio->setAttribute('Имя', 'Михаил');
                $fio->setAttribute('Отчество', 'Сергеевич');
            }
        }

        $warnings = $this->injectMarkCodes($dom, $xp, $invoice);

        $origName = $file->getClientOriginalName();
        $filename = $oldNumber !== '' && str_contains($origName, $oldNumber)
            ? str_replace($oldNumber, $newNumber, $origName)
            : "upd_{$newNumber}.xml";

        return [
            'xml' => $dom->saveXML(),
            'filename' => $filename,
            'warnings' => $warnings,
        ];
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
        foreach ($xp->query('//ТаблСчФакт/СведТов') as $tov) {
            $num = $tov->getAttribute('НомСтр');
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
