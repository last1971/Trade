<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Exports\InvoiceExport;
use App\Http\Requests\IndexRequest;
use App\Http\Requests\InvoicePDFRequest;
use App\Http\Resources\InvoiceResource;
use App\Invoice;
use App\Services\AtolService;
use App\Services\InvoiceLineService;
use App\Services\InvoiceService;
use GuzzleHttp\Exception\GuzzleException;
use Maatwebsite\Excel\Excel;
use PDF;
use PhpOffice\PhpSpreadsheet\Writer\Exception;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

/**
 * Class InvoiceController
 * @package App\Http\Controllers\Api
 */
class InvoiceController extends ModelController
{
    /**
     * InvoiceController constructor.
     */
    public function __construct()
    {
        parent::__construct(InvoiceService::class, InvoiceResource::class);
    }

    /**
     * @param Excel $excel
     * @param InvoiceExport $export
     * @param $extension
     * @return BinaryFileResponse
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws Exception
     */
    public function export(Excel $excel, InvoiceExport $export)
    {
        return $excel->download($export, 'invoices.xlsx');
    }

    public function pdf(InvoicePDFRequest $request)
    {
        $request->merge([
            'lines' => (new InvoiceLineService())->index(collect([
                'with' => ['category', 'good', 'name'],
                'filterAttributes' => ['invoice.SCODE'],
                'filterOperators' => ['='],
                'filterValues' => [$request->invoice->SCODE],
                'sortBy' => $request->sortBy,
                'sortDesc' =>$request->sortDesc,
            ]))->get()
        ]);
        return PDF::loadView('invoice-pdf', $request->all())->download('invoice.pdf');
    }

    /**
     * @param Invoice $invoice
     * @param IndexRequest $request
     * @param AtolService $atol
     * @return string
     * @throws ApiException
     * @throws GuzzleException
     * @throws Throwable
     */
    public function receipt(Invoice $invoice, IndexRequest $request) //, AtolService $atol)
    {
        /*
        try {
            $lines = $invoice->invoiceLines()->with('good.name')->get();
            $atol->operator = $request->user();
            $atol->receipt(
                $lines->map(fn($line) => [
                    'name' => $line->good->name->NAME,
                    'price' => $line->PRICE,
                    'quantity' => $line->QUAN,
                    'amount' => $line->SUMMAP
                ])->toArray(),
                'sell',
                'cash',
                $invoice->buyer,
            );
            return 'OK';
        } catch (\Exception $e) {
            throw new ApiException($e->getMessage());
        }
        */
        $pickUps  = $invoice
            ->pickUps()
            ->with('good.name')
            ->whereRaw('QUANSKLADNEED - QUANSKLAD > 0')
            ->get();
        $employee = $request->user()->employee;
        $pdf = PDF::loadView(
            'stock-etik', compact('employee', 'invoice', 'pickUps')
        );
        $pdf->setPaper([0, 0, 219, 151]);
        return $pdf->download('test-etik.pdf');
    }
}
