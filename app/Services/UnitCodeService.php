<?php

namespace App\Services;

use App\UnitCode;

class UnitCodeService extends ModelService
{
    public function __construct()
    {
        parent::__construct(UnitCode::class);
    }
}
