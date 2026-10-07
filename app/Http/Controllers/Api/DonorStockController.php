<?php

namespace App\Http\Controllers\Api;

use App\Exports\DonorStockExport;
use App\Http\Controllers\Controller;
use App\Services\DonorStockService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Excel;

class DonorStockController extends Controller
{
    /**
     * JSON для страницы «Доноры». Зовёт тот же сервис, что и export — ничего не пересчитывает.
     */
    public function list(Request $request, DonorStockService $service): array
    {
        return $service->list($this->marketplace($request));
    }

    /**
     * Excel того же списка (тот же параметр, тот же сервис).
     */
    public function export(Request $request, Excel $excel, DonorStockService $service)
    {
        $report = $service->list($this->marketplace($request));

        return $excel->download(new DonorStockExport($report), 'Доноры.xlsx');
    }

    private function marketplace(Request $request): string
    {
        return $request->validate([
            'marketplace' => 'required|in:' . implode(',', DonorStockService::marketplaces()),
        ])['marketplace'];
    }
}
