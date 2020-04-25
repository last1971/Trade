<?php


namespace App\Http\Controllers\Api;


use App\Http\Resources\OrderLineResource;
use App\Services\OrderLineService;

class OrderLineController extends ModelController
{
    public function __construct()
    {
        parent::__construct(OrderLineService::class, OrderLineResource::class);
    }
}
