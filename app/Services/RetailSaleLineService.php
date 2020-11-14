<?php


namespace App\Services;


use App\RetailSaleLine;
use Illuminate\Support\Facades\DB;

class RetailSaleLineService extends ModelService
{
    public function __construct()
    {
        parent::__construct(RetailSaleLine::class);

        $this->addSelect = [
            DB::raw('QUANSHOP * PRICE AS AMOUNT'),
            DB::raw('1 - PRICE / PRICESHOP AS DISCOUNT')
        ];
    }
}
