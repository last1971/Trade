<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Excel-отчёт по долгам покупателя: лист «Счета» (с формулами) + лист «Не едет».
 * Данные берёт из готового результата BuyerDebtService::report() — ничего не считает.
 */
class BuyerDebtExport implements WithMultipleSheets
{
    public function __construct(private array $report)
    {
    }

    public function sheets(): array
    {
        return [
            new BuyerDebtSummarySheet($this->report),
            new BuyerDebtUpdsSheet($this->report),
            new BuyerDebtItemsSheet($this->report),
        ];
    }
}
