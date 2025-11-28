<?php

namespace App\Services;

use App\AdvancedBuyer;
use App\Firm;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Carbon\Carbon;

class UpdImportService
{
    /**
     * Парсинг УПД из Excel файла 1С
     * @param UploadedFile $file
     * @return array
     */
    public function parse(UploadedFile $file): array
    {
        $reader = new Xlsx();
        $spreadsheet = $reader->load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        // Номер и дата из первой строки
        $number = $this->cleanNumber($rows[0][15] ?? '');
        $date = $this->parseDate($rows[0][24] ?? '');

        // ИНН/КПП продавца из строки 5, позиция 17
        $sellerInnKpp = trim($rows[5][17] ?? '');

        // Парсим строки товаров (начиная с 14-й строки)
        $lines = [];
        $lastProductRow = 14;
        for ($i = 14; $i < count($rows); $i++) {
            $row = $rows[$i];

            // Проверяем, что это строка товара (есть код и наименование)
            // Строка "Всего к оплате" - конец товаров
            if (isset($row[5]) && stripos((string)($row[5] ?? ''), 'Всего') !== false) {
                $lastProductRow = $i;
                break;
            }

            // Пропускаем если нет кода товара или он не числовой
            $code = $this->cleanNumber($row[1] ?? '');
            if (empty($code) || !is_numeric($code)) {
                continue;
            }

            $amountWithoutVat = $this->parseNumber($row[39] ?? '0');
            $vatAmount = $this->parseNumber($row[54] ?? $row[53] ?? '0');

            $lines[] = [
                'code' => $code,
                'name' => trim($row[9] ?? ''),
                'unit_code' => $this->cleanNumber($row[22] ?? '796'),
                'unit_name' => trim($row[24] ?? 'шт'),
                'quantity' => $this->parseNumber($row[26] ?? '0'),
                'price_without_vat' => $this->parseNumber($row[29] ?? '0'),
                'amount_without_vat' => $amountWithoutVat,
                'vat_amount' => $vatAmount,
                'amount_with_vat' => $amountWithoutVat + $vatAmount,
            ];
        }

        // Основание (уведомление о выкупе и т.п.) - строка +6 от "Всего", позиция 19
        $basisRaw = trim($rows[$lastProductRow + 6][19] ?? '');
        $basisParsed = $this->parseBasis($basisRaw);

        return [
            'number' => $number,
            'date' => $date,
            'seller_inn_kpp' => $sellerInnKpp,
            'basis' => $basisParsed['name'],
            'basisNumber' => $basisParsed['number'],
            'basisDate' => $basisParsed['date'],
            'lines' => $lines,
            'totals' => $this->calculateTotals($lines),
        ];
    }

    /**
     * Очистка номера от пробелов
     */
    private function cleanNumber($value): string
    {
        return trim(str_replace([' ', "\xc2\xa0"], '', (string)$value));
    }

    /**
     * Парсинг даты из формата "9 ноября 2025 г."
     */
    private function parseDate(string $dateStr): ?Carbon
    {
        $months = [
            'января' => 1, 'февраля' => 2, 'марта' => 3, 'апреля' => 4,
            'мая' => 5, 'июня' => 6, 'июля' => 7, 'августа' => 8,
            'сентября' => 9, 'октября' => 10, 'ноября' => 11, 'декабря' => 12,
        ];

        $dateStr = trim($dateStr);
        if (preg_match('/(\d+)\s+(\S+)\s+(\d{4})/u', $dateStr, $matches)) {
            $day = (int)$matches[1];
            $monthName = mb_strtolower($matches[2]);
            $year = (int)$matches[3];

            if (isset($months[$monthName])) {
                return Carbon::create($year, $months[$monthName], $day);
            }
        }

        return null;
    }

    /**
     * Парсинг числа из строки (с запятыми и пробелами)
     * Формат из 1С: "1,307.23" (запятая - разделитель тысяч, точка - десятичный)
     * или "1 307,23" (пробел - разделитель тысяч, запятая - десятичный)
     */
    private function parseNumber($value): float
    {
        $value = (string)$value;
        // Убираем все виды Unicode пробелов (Zs - space separators)
        $value = preg_replace('/\p{Zs}/u', '', $value);

        // Определяем формат: если есть и запятая и точка
        if (strpos($value, ',') !== false && strpos($value, '.') !== false) {
            // "1,307.23" - запятая как разделитель тысяч, точка как десятичный
            $value = str_replace(',', '', $value);
        } else {
            // "1307,23" - запятая как десятичный разделитель
            $value = str_replace(',', '.', $value);
        }

        return (float)$value;
    }

    /**
     * Парсинг основания: "Уведомление о выкупе 530481386 от 03.11.2025"
     * @return array{name: string, number: string, date: string}
     */
    private function parseBasis(string $value): array
    {
        $name = $value;
        $number = '';
        $date = '';

        // Ищем номер (цифры после № или перед "от")
        if (preg_match('/№?\s*(\d+)\s+от/ui', $value, $matches)) {
            $number = $matches[1];
            // Название - всё до номера
            $name = trim(preg_replace('/№?\s*\d+\s+от.*/ui', '', $value));
        }

        // Ищем дату (формат дд.мм.гггг)
        if (preg_match('/(\d{2}\.\d{2}\.\d{4})/u', $value, $matches)) {
            $date = $matches[1];
        }

        return [
            'name' => $name,
            'number' => $number,
            'date' => $date,
        ];
    }

    /**
     * Расчет итогов
     */
    private function calculateTotals(array $lines): array
    {
        $amountWithoutVat = 0;
        $vatAmount = 0;
        $amountWithVat = 0;

        foreach ($lines as $line) {
            $amountWithoutVat += $line['amount_without_vat'];
            $vatAmount += $line['vat_amount'];
            $amountWithVat += $line['amount_with_vat'];
        }

        return [
            'amount_without_vat' => round($amountWithoutVat, 2),
            'vat_amount' => round($vatAmount, 2),
            'amount_with_vat' => round($amountWithVat, 2),
        ];
    }

    /**
     * Генерация XML из xlsx файла
     * @param UploadedFile $file
     * @param int $buyerId - ID покупателя с ЭДО
     * @return string
     */
    public function generateXml(UploadedFile $file, int $buyerId): string
    {
        $parsed = $this->parse($file);

        // Получаем покупателя с advancedBuyer
        $advancedBuyer = AdvancedBuyer::with('buyer')->where('buyer_id', $buyerId)->firstOrFail();
        $buyer = $advancedBuyer->buyer;
        $buyer->setRelation('advancedBuyer', $advancedBuyer);

        // Получаем фирму по ИНН из xlsx
        $firm = null;
        if (!empty($parsed['seller_inn_kpp'])) {
            $inn = explode('/', $parsed['seller_inn_kpp'])[0] ?? '';
            if ($inn) {
                // Используем LIKE из-за CHAR полей в Firebird
                $firm = Firm::where('INN', 'LIKE', $inn . '%')->first();
            }
        }
        if (!$firm) {
            throw new \Exception('Фирма с ИНН ' . ($parsed['seller_inn_kpp'] ?? 'не указан') . ' не найдена');
        }

        // Формируем fileId
        $fileId = 'ON_NSCHFDOPPR_' . $advancedBuyer->edo_id .
            '_' . $firm->EDOID . '_' . Carbon::now()->format('Ymd') . '-' . Str::uuid();

        // Создаём объект-обёртку для transferOut
        $transferOut = new \stdClass();
        $transferOut->DATA = $parsed['date'];
        $transferOut->NSF = $parsed['number'];
        $transferOut->firm = $firm;
        $transferOut->firmHistory = null;
        $transferOut->buyer = $buyer;
        $transferOut->invoice = (object)[
            'DATA' => $parsed['date'],
            'NS' => $parsed['number'],
            'NZ' => null,
            'IGK' => null,
            'basis' => $parsed['basis'] ?? null,
            'basisNumber' => $parsed['basisNumber'] ?? null,
            'basisDate' => $parsed['basisDate'] ?? null,
        ];

        // Формируем строки
        $transferOutLines = collect($parsed['lines'])->map(function ($line) {
            $obj = new \stdClass();
            $obj->QUAN = $line['quantity'];
            $obj->SUMMAP = $line['amount_with_vat'];
            $obj->amountWithoutVat = $line['amount_without_vat'];
            $obj->priceWithoutVat = $line['price_without_vat'];
            $obj->GOODSCODE = $line['code'];
            $obj->countryNumCode = null;
            $obj->GTD = null;
            $obj->STRANA = null;

            // Имитация связей name и good
            $obj->name = (object)['NAME' => $line['name']];
            $obj->good = (object)[
                'unitCode' => $line['unit_code'],
                'unitName' => $line['unit_name'],
            ];

            return $obj;
        });

        $cashFlows = collect([]);

        $output = View::make('transfer-out-xml')
            ->with(compact('fileId', 'transferOut', 'transferOutLines', 'cashFlows'))
            ->render();

        return "<?xml version=\"1.0\" encoding=\"windows-1251\" ?> \n" . iconv("utf-8", "cp1251", $output);
    }
}
