<?php


namespace App\Http\Controllers\Api;


use App\Services\AdvancedBuyerService;

class AdvancedBuyerController extends ModelController
{
    public function __construct()
    {
        parent::__construct(AdvancedBuyerService::class);
    }
}
