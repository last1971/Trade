<?php

namespace App\Http\Controllers\Api;


use App\Services\RetailSaleLineService;

class RetailSaleLineController extends ModelController
{
    public function __construct()
    {
        parent::__construct(RetailSaleLineService::class);
    }
}
