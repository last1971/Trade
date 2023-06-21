<?php

namespace App\Services;

use App\CashFlow;

class CashFlowService extends ModelService {
    public function __construct()
    {
        parent::__construct(CashFlow::class);
    }
}
