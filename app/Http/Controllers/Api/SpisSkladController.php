<?php

namespace App\Http\Controllers\Api;


use App\Services\SpisSkladService;

class SpisSkladController extends ModelController
{
    public function __construct()
    {
        parent::__construct(SpisSkladService::class);
    }
}
