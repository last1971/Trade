<?php

namespace App\Http\Controllers\Api;


use App\Services\FirmHistoryService;

class FirmHistoryController extends ModelController
{
    public function __construct()
    {
        parent::__construct(FirmHistoryService::class);
    }
}
