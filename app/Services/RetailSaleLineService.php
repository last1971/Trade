<?php


namespace App\Services;


use App\RetailSaleLine;

class RetailSaleLineService extends ModelService
{
    public function __construct()
    {
        parent::__construct(RetailSaleLine::class);
    }
}
