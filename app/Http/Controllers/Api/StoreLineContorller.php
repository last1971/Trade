<?php

namespace App\Http\Controllers\Api;


use App\StoreLine;

class StoreLineContorller extends ModelController
{
    public function __construct()
    {
        parent::__construct(StoreLine::class);
    }
}
