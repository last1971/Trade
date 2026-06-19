<?php

namespace App\Http\Controllers\Api;

use App\Exports\BuyerDebtExport;
use App\Http\Controllers\Controller;
use App\Services\BuyerDebt\BuyerDebtService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Excel;

class BuyerDebtController extends Controller
{
    /**
     * Excel-отчёт по долгам покупателя (?buyer=POKUPATCODE).
     */
    public function export(Request $request, Excel $excel, BuyerDebtService $service)
    {
        $report = $service->report((string)$request->buyer, $request->from ?: null);
        $name = $report['buyer']['name'] ?: $request->buyer;

        return $excel->download(new BuyerDebtExport($report), "Долги {$name}.xlsx");
    }

    /**
     * JSON-версия того же отчёта для показа на странице (?buyer=POKUPATCODE).
     * Зовёт тот же сервис, что и export — ничего не пересчитывает.
     */
    public function report(Request $request, BuyerDebtService $service): array
    {
        return $service->report((string)$request->buyer, $request->from ?: null);
    }

    /**
     * Сводный отчёт по всем покупателям за период (?from=YYYY-MM-DD&to=YYYY-MM-DD).
     * Долговые колонки — тем же сервисом, что и report (цифры совпадают).
     */
    public function summary(Request $request, BuyerDebtService $service): array
    {
        return $service->summary($request->from ?: null, $request->to ?: null);
    }
}
