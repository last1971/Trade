<?php

namespace App\Http\Controllers\Api;

use App\Services\TransferOutLineService;

class TransferOutLineController extends ModelController
{
    public function __construct()
    {
        parent::__construct(TransferOutLineService::class);
    }
}
