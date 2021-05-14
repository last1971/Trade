<?php


namespace App\Services;


use App\StoreLine;

class StoreLineService extends ModelService
{
    public function __construct()
    {
        parent::__construct(StoreLine::class);
    }
}
