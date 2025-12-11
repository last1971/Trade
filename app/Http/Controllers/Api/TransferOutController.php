<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\ModelRequest;
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

    public function pdfToken($id)
    {
        // Проверяем валидность стран перед генерацией токена
        $lines = TransferOutLine::where('SFCODE', $id)->get();
        $countryCodes = config('country_codes');
        foreach ($lines as $line) {
            $strana = trim($line->STRANA ?? '');
            if ($strana && !array_key_exists(\Illuminate\Support\Str::upper($strana), $countryCodes)) {
                return response()->json([
                    'message' => 'Неизвестная страна: ' . $strana
                ], 400);
            }
        }

        // Генерируем одноразовый токен на 5 минут
        $token = \Illuminate\Support\Str::random(64);
        \Illuminate\Support\Facades\Cache::put('pdf_token_' . $token, [
            'user_id' => auth()->id(),
            'transfer_out_id' => $id,
        ], now()->addMinutes(5));

        return response()->json(['token' => $token]);
    }

    public function pdfPublic($id, \Illuminate\Http\Request $request)
    {
        $transferOut = \App\TransferOut::with('firm', 'buyer.advancedBuyer', 'employee', 'invoice.cashFlows')
            ->findOrFail(intval($id));

        $params = [
            'body' => $request->get('body') === 'true',
            'producer' => $request->get('producer') === 'true',
            'category' => $request->get('category') === 'true',
            'divider' => $request->get('divider') === 'true',
            'basis' => $request->get('basis'),
            'basisNumber' => $request->get('basisNumber'),
            'basisDate' => $request->get('basisDate'),
        ];

        return $this->generatePdf($transferOut, $params);
    }

    public function pdf(TransferOutPDFRequest $request)
    {
        return $this->generatePdf($request->transferOut, $request->validated());
    }

    private function generatePdf($transferOut, array $params)
    {
        $cashFlows = $transferOut->invoice->cashFlows->filter(function ($v) {
            return !$v->SFCODE1;
        });
        $transferOutLines = TransferOutLine::with(['category', 'good', 'name'])
            ->where('SFCODE', '=', $transferOut->SFCODE)
            ->get();
        $count = 0;
        $pdf = PDF::loadView(
            'transfer-out-pdf',
            array_merge(
                $params,
                compact('transferOutLines', 'transferOut', 'cashFlows', 'count')
            )
        );
        $pdf->setPaper('A4', 'landscape');
        $proxy = $pdf->getDomPdf();
        $proxy->render();
        $count = $proxy->getCanvas()->get_page_count();
        $pdf = PDF::loadView(
            'transfer-out-pdf',
            array_merge(
                $params,
                compact('transferOutLines', 'transferOut', 'cashFlows', 'count')
            )
        );
        $pdf->setPaper('A4', 'landscape');

        $date = $transferOut->DATDOK instanceof \DateTime
            ? $transferOut->DATDOK->format('d.m.Y')
            : \Carbon\Carbon::parse($transferOut->DATDOK)->format('d.m.Y');

        $filename = 'УПД № ' . $transferOut->NSF . ' от ' . $date . '.pdf';

        return $pdf->stream($filename, [
            'Content-Disposition' => 'inline; filename="' . $filename . '"'
        ]);
    }

    public function store(ModelRequest $request)
    {
        $request->merge([
            'SCODE' => $request->item['SCODE'],
            'STAFF_ID' => $request->user()->employee->ID,
        ]);
        return $this->service->create($request);
    }
}
