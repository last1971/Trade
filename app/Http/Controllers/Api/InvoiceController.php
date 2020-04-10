<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\InvoiceRequest;
use App\Services\InvoiceService;

class InvoiceController extends ModelController
{
    public function __construct()
    {
        parent::__construct(InvoiceService::class);
    }
}
