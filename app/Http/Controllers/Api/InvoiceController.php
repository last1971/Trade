<?php

namespace App\Http\Controllers\Api;

use App\Exports\InvoiceExport;
use App\Http\Resources\InvoiceResource;
use App\Services\InvoiceService;
use Maatwebsite\Excel\Excel;
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
    public function export(Excel $excel, InvoiceExport $export, $extension)
    {
        return $excel->download($export, 'invoices.xlsx');
    }
}
