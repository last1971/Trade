<?php

namespace App\Http\Controllers\Api;

use App\Services\SellerOrderService;

class SellerOrderController extends ModelController
{
    public function __construct()
    {
        parent::__construct(SellerOrderService::class);
    }
}
