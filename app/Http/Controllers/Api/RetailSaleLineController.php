<?php

namespace App\Http\Controllers\Api;


use App\Http\Requests\ModelRequest;
use App\Services\RetailSaleLineService;

class RetailSaleLineController extends ModelController
{
    public function __construct()
    {
        parent::__construct(RetailSaleLineService::class);
    }

    public function destroy(ModelRequest $request, $id)
    {
        return 1;
    }
}
