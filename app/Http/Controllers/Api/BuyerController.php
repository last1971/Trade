<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\BuyerResource;
use App\Services\BuyerService;

class BuyerController extends ModelController
{
    //
    public function __construct()
    {
        parent::__construct(BuyerService::class, BuyerResource::class);
    }
}
