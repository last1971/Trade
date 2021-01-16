<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ModelRequest;
use App\Payment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;

class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return LengthAwarePaginator
     */
    public function index(): LengthAwarePaginator
    {
        return Payment::requestBuilder()->paginate(request('items_per_page'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param ModelRequest $request
     * @return Payment|Builder|Model
     */
    public function store(ModelRequest $request): Payment
    {
        $item = $request->validated()['item'];
        $item['user_id'] = $request->user()->id;
        return Payment::query()->create($item);
    }

    /**
     * Display the specified resource.
     *
     * @param Payment $payment
     * @return Payment
     */
    public function show(Payment $payment)
    {
        return $payment;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param ModelRequest $request
     * @param Payment $payment
     * @return Payment
     */
    public function update(ModelRequest $request, Payment $payment): Payment
    {
        $item = $request->validated()['item'];
        $payment->update($item);
        $payment->load('seller');
        return $payment;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function destroy(Payment $payment): JsonResponse
    {
        $payment->delete();
        return response()->json(['message' => 'Successfully deleted']);
    }
}
