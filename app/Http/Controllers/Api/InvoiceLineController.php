<?php

namespace App\Http\Controllers\Api;

use App\Services\InvoiceLineService;


class InvoiceLineController extends ModelController
{
    public function __construct()
    {
        parent::__construct(InvoiceLineService::class);
    }
}
