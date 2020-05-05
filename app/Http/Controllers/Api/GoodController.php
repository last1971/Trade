<?php


namespace App\Http\Controllers\Api;


use App\Services\GoodService;

class GoodController extends ModelController
{
    public function __construct()
    {
        parent::__construct(GoodService::class);
    }
}
