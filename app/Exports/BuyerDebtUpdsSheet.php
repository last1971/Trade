<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

/**
 * Лист «Неоплаченные УПД»: УПД с непогашенным остатком (частично + не оплачен), по всем счетам.
 */
class BuyerDebtUpdsSheet extends AbstractBuyerDebtSheet
{
    public function title(): string
    {
        return 'Неоплаченные УПД';
    }

    public function headings(): array
    {
        return ['Счёт', 'УПД №', 'Дата', 'Сумма', 'Оплачено', 'Остаток', 'Статус'];
    }

    /** УПД с остатком > 0 по всем счетам. */
    private function rows(): array
    {
        $rows = [];
        foreach ($this->report['invoices'] as $inv) {
            foreach ($inv['upds'] as $upd) {
                if ($upd['remaining'] > 0) {
                    $rows[] = ['NS' => $inv['NS']] + $upd;
                }
            }
        }
        return $rows;
    }

    public function array(): array
    {
        return array_map(fn($r) => [
            $r['NS'],
            $r['NSF'],
            Date::dateTimeToExcel(new Carbon($r['DATA'])),
            $r['summa'],
            $r['paid'],
            $r['remaining'],
            $r['status'],
        ], $this->rows());
    }

    public function columnFormats(): array
    {
        return [
            'C' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'D' => self::NUM, 'E' => self::NUM, 'F' => self::NUM,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $count = count($this->rows());
                if ($count === 0) {
                    return;
                }

                $this->totalsRow($sheet, 2, $count + 1, 'C', ['D', 'E', 'F']);
            },
        ];
    }
}
