<?php


namespace App\Http\Controllers\Api;


use App\Services\OrderService;

class OrderController extends ModelController
{
    public function __construct()
    {
        parent::__construct(OrderService::class);
    }
}
