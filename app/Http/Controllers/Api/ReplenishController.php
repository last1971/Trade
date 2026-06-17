<?php

namespace App\Http\Controllers\Api;

use App\Exports\ReplenishListExport;
use App\Exports\ReplenishReportExport;
use App\Http\Controllers\Controller;
use App\Services\Replenish\ReplenishService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Excel;

class ReplenishController extends Controller
{
    /**
     * JSON массового отчёта «что закупить» для показа на странице.
     * Зовёт тот же сервис, что и listExport — ничего не пересчитывает.
     */
    public function list(Request $request, ReplenishService $service): array
    {
        return $service->list(
            (int)$request->lead,
            (int)($request->period1 ?: 30),
            (int)($request->period2 ?: 180),
            (int)($request->min_sales ?: 2)
        );
    }

    /**
     * JSON детального отчёта по одному товару для показа на странице.
     * Зовёт тот же сервис, что и reportExport — ничего не пересчитывает.
     */
    public function report(Request $request, ReplenishService $service): array
    {
        return $service->report(
            (int)$request->good,
            (int)$request->lead,
            (int)($request->period ?: 180)
        );
    }

    /**
     * Excel массового отчёта «что закупить» (те же параметры, тот же сервис).
     */
    public function listExport(Request $request, Excel $excel, ReplenishService $service)
    {
        $report = $service->list(
            (int)$request->lead,
            (int)($request->period1 ?: 30),
            (int)($request->period2 ?: 180),
            (int)($request->min_sales ?: 2)
        );

        return $excel->download(new ReplenishListExport($report), 'Что закупить.xlsx');
    }

    /**
     * Excel детального отчёта по одному товару (те же параметры, тот же сервис).
     */
    public function reportExport(Request $request, Excel $excel, ReplenishService $service)
    {
        $report = $service->report(
            (int)$request->good,
            (int)$request->lead,
            (int)($request->period ?: 180)
        );
        $name = $report['good']['name'] ?: $report['good']['GOODSCODE'];

        return $excel->download(new ReplenishReportExport($report), "Закупка {$name}.xlsx");
    }
}
