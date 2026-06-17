<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

/**
 * Excel массового отчёта «что закупить». Данные берёт из готового ReplenishService::list() — ничего не считает.
 */
class ReplenishListExport implements FromArray, WithHeadings, WithColumnFormatting, WithTitle, ShouldAutoSize
{
    public function __construct(private array $report)
    {
    }

    public function title(): string
    {
        return 'Что закупить';
    }

    public function headings(): array
    {
        return ['Код', 'Название', 'Корпус', 'Производитель', 'Продано', 'Заказать'];
    }

    public function array(): array
    {
        return array_map(fn($row) => [
            $row['GOODSCODE'],
            $row['name'],
            $row['body'],
            $row['producer'],
            $row['soldPeriod1'],
            $row['toOrder'],
        ], $this->report['rows']);
    }

    public function columnFormats(): array
    {
        return [
            'E' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'F' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }
}
