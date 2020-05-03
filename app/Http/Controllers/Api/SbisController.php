<?php

namespace App\Http\Controllers\Api;

use App\Exports\SbisExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\SbisRequest;
use App\Services\TransferOutLineService;
use Maatwebsite\Excel\Excel;

class SbisController extends Controller
{
    //
    public function xlsx(Excel $excel, SbisExport $export)
    {
        return $excel->download($export, 'transfer-out-lines.xlsx');
    }

    public function clearGtd(SbisRequest $request, TransferOutLineService $service)
    {
        $service->clearGtd($request);
    }
}
