<?php


namespace App\Services;


use App\RetailSale;

class RetailSaleService extends ModelService
{
    public function __construct()
    {
        parent::__construct(RetailSale::class);
    }
}
