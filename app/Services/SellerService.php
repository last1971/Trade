<?php


namespace App\Services;


use App\Seller;

class SellerService extends ModelService
{
    public function __construct()
    {
        parent::__construct(Seller::class);
    }
}
