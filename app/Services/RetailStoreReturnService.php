<?php


namespace App\Services;


use App\RetailStoreReturn;
use Illuminate\Support\Facades\DB;

class RetailStoreReturnService extends ModelService
{
    public function __construct()
    {
        parent::__construct(RetailStoreReturn::class);

        $this->addSelect = [
            DB::raw('QUANSHOP * PRICE AS AMOUNT'),
        ];
    }
}
