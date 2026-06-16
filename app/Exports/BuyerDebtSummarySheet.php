<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

/**
 * Лист «Счета»: строка на счёт + итоги и долг ФОРМУЛАМИ (пересчёт при правке/удалении строк).
 */
class BuyerDebtSummarySheet extends AbstractBuyerDebtSheet
{
    public function title(): string
    {
        return 'Счета';
    }

    public function headings(): array
    {
        return ['Счёт', 'Дата', 'Статус', 'Сумма', 'Оплачено', 'Депозит', 'Отгружено', 'Долг', 'К отгрузке', 'На складе', 'Едет', 'Не едет'];
    }

    public function array(): array
    {
        return array_map(fn($inv) => [
            $inv['NS'],
            Date::dateTimeToExcel(new Carbon($inv['DATA'])),
            $inv['statusLabel'],
            $inv['sum'],
            $inv['paidBank'],
            $inv['deposit'],
            $inv['shipped'],
            $inv['debt'],            // перезапишется формулой
            $inv['remainingToShip'], // перезапишется формулой
            $inv['onHand'],
            $inv['coming'],
            $inv['notComing'],
        ], $this->report['invoices']);
    }

    public function columnFormats(): array
    {
        return [
            'B' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'D' => self::NUM, 'E' => self::NUM, 'F' => self::NUM, 'G' => self::NUM,
            'H' => self::NUM, 'I' => self::NUM, 'J' => self::NUM, 'K' => self::NUM,
            'L' => self::NUM,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $count = count($this->report['invoices']);
                if ($count === 0) {
                    return;
                }

                $first = 2;
                $last = $count + 1;

                // Построчные формулы: Долг = Сумма−Оплачено−Депозит; К отгрузке = Сумма−Отгружено.
                for ($r = $first; $r <= $last; $r++) {
                    $sheet->setCellValue("H{$r}", "=D{$r}-E{$r}-F{$r}");
                    $sheet->setCellValue("I{$r}", "=D{$r}-G{$r}");
                }

                // Строка ИТОГО.
                $this->totalsRow($sheet, $first, $last, 'A', ['D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L']);

                // Итоговый расчёт долга (формулы от строки ИТОГО). «Не едет» — колонка L.
                $total = $last + 1;
                $oweRow = $total + 2;
                $sheet->setCellValue("G{$oweRow}", 'Должны денег');
                $sheet->setCellValue("H{$oweRow}", "=H{$total}");
                $sheet->setCellValue('G' . ($oweRow + 1), 'Конкретно должны');
                $sheet->setCellValue('H' . ($oweRow + 1), "=H{$total}-L{$total}");
                $sheet->getStyle("G{$oweRow}:H" . ($oweRow + 1))->getFont()->setBold(true);
                $sheet->getStyle("H{$oweRow}:H" . ($oweRow + 1))
                    ->getNumberFormat()->setFormatCode(self::NUM);
            },
        ];
    }
}
