<?php

namespace App\Http\Controllers\Api;

use App\Services\CashFlowService;

class CashFlowController extends ModelController
{
    public function __construct() {
        parent::__construct(CashFlowService::class);
    }
}
