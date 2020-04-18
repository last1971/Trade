<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\TransferOutResource;
use App\Services\TransferOutService;

class TransferOutController extends ModelController
{
    public function __construct()
    {
        parent::__construct(TransferOutService::class, TransferOutResource::class);
    }
}
