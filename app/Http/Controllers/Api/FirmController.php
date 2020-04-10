<?php

namespace App\Http\Controllers\Api;

use App\Services\FirmService;

class FirmController extends ModelController
{
    //
    public function __construct()
    {
        parent::__construct(FirmService::class);
    }
}
