<?php

namespace App\Http\Controllers\Api;

use App\Services\BuyerService;

class BuyerController extends ModelController
{
    //
    public function __construct()
    {
        parent::__construct(BuyerService::class);
    }
}
