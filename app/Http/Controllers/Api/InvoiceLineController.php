<?php

namespace App\Http\Controllers\Api;

use App\Exports\InvoiceLineExport;
use App\Http\Resources\InvoiceLineResource;
use App\Services\InvoiceLineService;
use Maatwebsite\Excel\Excel;


class InvoiceLineController extends ModelController
{
    public function __construct()
    {
        parent::__construct(InvoiceLineService::class, InvoiceLineResource::class);
    }

    public function export(Excel $excel, InvoiceLineExport $export)
    {
        return $excel->download($export, 'invoice-lines.xlsx');
    }
}
