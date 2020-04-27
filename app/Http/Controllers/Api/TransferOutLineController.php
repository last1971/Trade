<?php

namespace App\Http\Controllers\Api;

use App\Exports\TransferOutLineExport;
use App\Http\Resources\TransferOutLineResource;
use App\Services\TransferOutLineService;
use Maatwebsite\Excel\Excel;

class TransferOutLineController extends ModelController
{
    public function __construct()
    {
        parent::__construct(TransferOutLineService::class, TransferOutLineResource::class);
    }

    public function export(Excel $excel, TransferOutLineExport $export)
    {
        return $excel->download($export, 'transfer-out-lines.xlsx');
    }
}
