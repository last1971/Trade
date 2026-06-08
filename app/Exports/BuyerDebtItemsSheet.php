<?php

namespace App\Exports;

use Maatwebsite\Excel\Events\AfterSheet;

/**
 * Лист «Не едет»: товары, которые нечем закрыть (по всем счетам). Сумма и итог — формулами.
 */
class BuyerDebtItemsSheet extends AbstractBuyerDebtSheet
{
    public function title(): string
    {
        return 'Не едет';
    }

    public function headings(): array
    {
        return ['Счёт', 'GOODSCODE', 'Товар', 'Кол-во', 'Цена', 'Сумма'];
    }

    private function holes(): array
    {
        $holes = [];
        foreach ($this->report['invoices'] as $inv) {
            foreach ($inv['holes'] as $hole) {
                $holes[] = $hole;
            }
        }
        return $holes;
    }

    public function array(): array
    {
        return array_map(fn($h) => [
            $h['NS'],
            $h['GOODSCODE'],
            $h['name'],
            $h['qty'],
            $h['price'],
            $h['sum'], // доля денег строки (SUMMAP), может отличаться от Кол-во×Цена из-за округления цены
        ], $this->holes());
    }

    public function columnFormats(): array
    {
        return ['D' => self::NUM, 'E' => self::NUM, 'F' => self::NUM];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $count = count($this->holes());
                if ($count === 0) {
                    return;
                }

                $this->totalsRow($sheet, 2, $count + 1, 'C', ['F']);
            },
        ];
    }
}
