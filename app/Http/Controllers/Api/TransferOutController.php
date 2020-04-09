<?php

namespace App\Http\Controllers\Api;

use App\Services\TransferOutService;

class TransferOutController extends ModelController
{
    public function __construct()
    {
        parent::__construct(TransferOutService::class);
    }
}
