<?php

namespace App\Http\Controllers\Api;

use App\Exports\SbisExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\SbisRequest;
use App\Rules\RightCountryRule;
use App\Rules\RightUnitRule;
use App\Services\SbisService;
use App\Services\TransferOutLineService;
use App\Services\TransferOutService;
use App\Services\UpdImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Excel;
use PDF;

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
            $this->validate($request, ['date' => [new RightUnitRule($transferOut), new RightCountryRule($transferOut)]]);
        }
        foreach ($transferOuts as $transferOut) {
            $sbisService->exportTransferOut($transferOutService->xml(collect(compact('transferOut'))));
        }
    }

    public function packingList(SbisRequest $request, TransferOutService $transferOutService)
    {
        $transferOuts = $transferOutService->index(collect([
            'filterAttributes' => ['POKUPATCODE', 'DATA'],
            'filterOperators' => ['=', 'BETWEENDATE'],
            'filterValues' => [$request->buyerId, [$request->date, Carbon::create($request->date)->addDay()]],
        ]))->get();
        /*
        $pdf = PDF::loadView(
            'packing-list-pdf',
            compact('transferOuts')
        );
        $pdf->setPaper('A4', 'portrait');
        $proxy = $pdf->getDomPdf();
        $proxy->render();
        $count = $proxy->getCanvas()->get_page_count();
        */
        $pdf = PDF::loadView(
            'packing-list-pdf',
            compact('transferOuts')
        );
        $pdf->setPaper('A4', 'portrait');
        return $pdf->download('packing-list-pdf.pdf');
    }

    /**
     * Импорт УПД из xlsx и генерация XML
     */
    public function updImport(Request $request, UpdImportService $service)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx',
            'buyer_id' => 'required|integer',
        ]);

        $xml = $service->generateXml($request->file('file'), $request->buyer_id);

        $filename = 'upd_' . date('Y-m-d_H-i-s') . '.xml';

        return response($xml, 200, [
            'Content-Type' => 'application/xml',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
