<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ModelRequest;
use App\PaymentOrder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;

class PaymentOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return LengthAwarePaginator
     */
    public function index(): LengthAwarePaginator
    {
        return PaymentOrder::requestBuilder()->paginate(request('items_per_page'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param ModelRequest $request
     * @return PaymentOrder|Builder|Model
     */
    public function store(ModelRequest $request)
    {
        $item = $request->validated()['item'];
        return PaymentOrder::query()->create($item);
    }

    /**
     * Display the specified resource.
     *
     * @param PaymentOrder $paymentOrder
     * @return PaymentOrder
     */
    public function show(PaymentOrder $paymentOrder): PaymentOrder
    {
        return $paymentOrder;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param ModelRequest $request
     * @param PaymentOrder $paymentOrder
     * @return PaymentOrder
     */
    public function update(ModelRequest $request, PaymentOrder $paymentOrder): PaymentOrder
    {
        $item = $request->validated()['item'];
        $paymentOrder->update($item);
        return $paymentOrder;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param PaymentOrder $paymentOrder
     * @return JsonResponse
     * @throws \Exception
     */
    public function destroy(PaymentOrder $paymentOrder): JsonResponse
    {
        $paymentOrder->delete();
        return response()->json(['message' => 'Successfully deleted']);
    }
}
