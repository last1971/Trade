<?php


namespace App\Services;


use App\Buyer;

class BuyerService extends ModelService
{
    public function __construct()
    {
        parent::__construct(Buyer::class);
    }
}
