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
}
