<?php

namespace App\Http\Controllers\Api;


use App\Services\StoreLineService;

class StoreLineController extends ModelController
{
    public function __construct()
    {
        parent::__construct(StoreLineService::class);
    }
}
