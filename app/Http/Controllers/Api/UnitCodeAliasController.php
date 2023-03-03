<?php

namespace App\Http\Controllers\Api;

use App\Services\UnitCodeAliasService;
use Illuminate\Http\Request;

class UnitCodeAliasController extends ModelController
{
    public function __construct()
    {
        parent::__construct(UnitCodeAliasService::class);
    }
}
