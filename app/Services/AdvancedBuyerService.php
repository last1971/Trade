<?php


namespace App\Services;


use App\AdvancedBuyer;

class AdvancedBuyerService extends ModelService
{
    public function __construct()
    {
        parent::__construct(AdvancedBuyer::class);
    }
}
