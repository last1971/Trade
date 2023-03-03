<?php

namespace App\Services;

use App\UnitCodeAlias;

class UnitCodeAliasService extends ModelService
{
    public function __construct()
    {
        parent::__construct(UnitCodeAlias::class);
    }
}
