<?php

namespace App\Http\Controllers\Api;

use App\Services\RetailSaleLineService;
use App\Services\RetailStoreReturnService;

class RetailStoreReturnController extends ModelController
{
    public function __construct()
    {
        parent::__construct(RetailStoreReturnService::class);
    }
}
