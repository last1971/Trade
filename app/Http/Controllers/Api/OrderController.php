<?php


namespace App\Http\Controllers\Api;


use App\Http\Requests\ModelRequest;
use App\Services\OrderService;
use Carbon\Carbon;

class OrderController extends ModelController
{
    public function __construct()
    {
        parent::__construct(OrderService::class);
    }

    public function store(ModelRequest $request)
    {
        $employee = $request->user()->employee;
        if (!$employee) {
            return response()->json(['message' => 'Только сотрудники могут создавать заказы'], 422);
        }
        $item = array_merge(
            $request->item, ['DATA_ZAK' => Carbon::now()->format('Y-m-d'), 'STAFF_ID' => $employee->ID]
        );
        $request->merge(compact('item'));
        return parent::store($request);
    }
}
