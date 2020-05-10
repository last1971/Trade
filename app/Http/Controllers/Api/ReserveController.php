<?php


namespace App\Http\Controllers\Api;


use App\Services\ReserveService;

class ReserveController extends ModelController
{
    public function __construct()
    {
        parent::__construct(ReserveService::class);
    }
}
