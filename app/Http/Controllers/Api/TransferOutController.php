<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\TransferOutPDFRequest;
use App\Http\Resources\TransferOutResource;
use App\Services\TransferOutService;
use App\TransferOutLine;
use PDF;

class TransferOutController extends ModelController
{
    public function __construct()
    {
        parent::__construct(TransferOutService::class, TransferOutResource::class);
    }

    public function xml(TransferOutPDFRequest $request)
    {
        $xml = $this->service->xml($request);
        $file = (string)(simplexml_load_string($xml))->attributes()["ИдФайл"];
        return response()
            ->streamDownload(function () use ($xml) {
                echo $xml;
            }, $file);
    }

    public function pdf(TransferOutPDFRequest $request)
    {
        $transferOut = $request->transferOut;
        $cashFlows = $transferOut->invoice->cashFlows->filter(function ($v) {
            return !$v->SFCODE1;
        });
        $transferOutLines = TransferOutLine::with(['category', 'good', 'name'])
            ->where('SFCODE', '=', $transferOut->SFCODE)
            ->get();
        $pdf = PDF::loadView('transfer-out-pdf', compact('transferOutLines', 'transferOut', 'cashFlows'));
        $pdf->setPaper('A4', 'landscape');
        return $pdf->download('transfer-out.pdf');
    }
}
