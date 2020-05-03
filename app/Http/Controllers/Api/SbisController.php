<?php

namespace App\Http\Controllers\Api;

use App\Exports\SbisExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\SbisRequest;
use App\Services\SbisService;
use App\Services\TransferOutLineService;
use App\Services\TransferOutService;
use Illuminate\Support\Carbon;
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

    public function export(SbisRequest $request, SbisService $sbisService, TransferOutService $transferOutService)
    {
        $transferOuts = $transferOutService->index(collect([
            'filterAttributes' => ['POKUPATCODE', 'DATA'],
            'filterOperators' => ['=', 'BETWEENDATE'],
            'filterValues' => [$request->buyerId, [$request->date, Carbon::create($request->date)->addDay()]],
        ]))->get();
        foreach ($transferOuts as $transferOut) {
            $sbisService->exportTransferOut($transferOutService->xml(collect(compact('transferOut'))));
        }
    }
}
