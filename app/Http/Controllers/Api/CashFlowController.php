<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\ModelRequest;
use App\Services\CashFlowService;

class CashFlowController extends ModelController
{
    public function __construct() {
        parent::__construct(CashFlowService::class);
    }

    public function store(ModelRequest $request)
    {
        $item = $request->get('item');
        $item['USERNAME'] = $request->user()->employee->FULLNAME;
        $request->merge(compact('item'));
        return parent::store($request);
    }
}
