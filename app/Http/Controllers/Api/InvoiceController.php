<?php

namespace App\Http\Controllers\Api;

use App\Exports\InvoiceExport;
use App\Http\Resources\InvoiceResource;
use App\Invoice;
use App\Services\InvoiceLineService;
use App\Services\InvoiceService;
use Maatwebsite\Excel\Excel;
use PDF;
use PhpOffice\PhpSpreadsheet\Writer\Exception;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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

    public function pdf($id)
    {
        $invoice = Invoice::with('firm', 'buyer')->findOrFail(intval($id));
        $lines = (new InvoiceLineService())->index(collect([
            'with' => ['category', 'good', 'name'],
            'filterAttributes' => ['invoice.SCODE'],
            'filterOperators' => ['='],
            'filterValues' => [$invoice->SCODE],
            'sortBy' => ['category.CATEGORY', 'name.NAME'],
            'sortDesc' => [false, false]
        ]))->get();
        return PDF::loadView('invoice-pdf', compact('invoice', 'lines'))->download('invoice.pdf');
    }
}
