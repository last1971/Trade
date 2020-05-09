<?php


namespace App\Http\Controllers\Api;


use App\Services\OrderStepService;

class OrderStepController extends ModelController
{
    public function __construct()
    {
        parent::__construct(OrderStepService::class);
    }
}
