<?php

namespace App\Http\Controllers\Api;

use App\Services\UnitCodeService;
use Illuminate\Http\Request;

class UnitCodeController extends ModelController
{
    public function __construct()
    {
        parent::__construct(UnitCodeService::class);
    }
}
