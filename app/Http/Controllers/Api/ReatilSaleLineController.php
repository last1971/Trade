<?php

namespace App\Http\Controllers\Api;


use App\Services\RetailSaleLineService;

class ReatilSaleLineController extends ModelController
{
    //
    public function __construct()
    {
        parent::__construct(RetailSaleLineService::class);
    }
}
