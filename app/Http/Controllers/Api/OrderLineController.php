<?php


namespace App\Http\Controllers\Api;


use App\Services\OrderLineService;

class OrderLineController extends ModelController
{
    public function __construct()
    {
        parent::__construct(OrderLineService::class);
    }
}
