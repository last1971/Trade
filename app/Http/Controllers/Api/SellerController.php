<?php


namespace App\Http\Controllers\Api;


use App\Services\SellerService;

class SellerController extends ModelController
{
    public function __construct()
    {
        parent::__construct(SellerService::class);
    }
}
