<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Excel страницы «Доноры». Данные берёт из готового DonorStockService::list() — ничего не считает.
 */
class DonorStockExport implements FromArray, WithHeadings, WithTitle, ShouldAutoSize
{
    public function __construct(private array $report)
    {
    }

    public function title(): string
    {
        return 'Доноры';
    }

    public function headings(): array
    {
        return ['Код', 'Наименование', 'Подобрано', 'Счетов'];
    }

    public function array(): array
    {
        $rows = array_map(fn($row) => [
            $row['GOODSCODE'],
            $row['name'],
            $row['quantity'],
            $row['invoices'],
        ], $this->report['rows']);

        $rows[] = ['', 'Итого: ' . $this->report['total']['goods'] . ' товаров', $this->report['total']['quantity'], ''];

        return $rows;
    }
}
