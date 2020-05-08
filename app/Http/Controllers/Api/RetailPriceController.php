<?php


namespace App\Http\Controllers\Api;


use App\Services\RetailPriceService;

class RetailPriceController extends ModelController
{
    public function __construct()
    {
        parent::__construct(RetailPriceService::class);
    }
}
