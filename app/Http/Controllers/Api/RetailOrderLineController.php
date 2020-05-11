<?php


namespace App\Http\Controllers\Api;


use App\Services\RetailOrderLineService;

class RetailOrderLineController extends ModelController
{
    public function __construct()
    {
        parent::__construct(RetailOrderLineService::class);
    }
}
