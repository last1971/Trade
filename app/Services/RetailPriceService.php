<?php


namespace App\Services;


use App\RetailPrice;

class RetailPriceService extends ModelService
{
    public function __construct()
    {
        parent::__construct(RetailPrice::class);
    }
}
