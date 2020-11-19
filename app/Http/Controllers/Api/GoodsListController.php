<?php

namespace App\Http\Controllers\Api;

use App\Buyer;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\GoodsListRequest;
use App\Services\AtolService;
use App\Services\GoodsListService;
use Exception;
use Illuminate\Support\Facades\DB;

class GoodsListController extends Controller
{
    private $paymentType = [
        'black' => 'BLACK',
        'cash' => 'CASH',
        'electronically' => 'CARD',
    ];

    public function store(GoodsListRequest $request, GoodsListService $service, AtolService $atol)
    {
        $service->isLocalTransaction();
        try {
            $date = $service->sale($request->lines, $request->user(), $request->buyerId);

            if ($request->paymentType !== 'black') {
                $atol->operator = $request->user();
                $atol->connect();
                $atol->receipt(
                    $request->lines,
                    'sell',
                    $request->paymentType,
                    Buyer::find($request->buyerId),
                );
            }

            $amount = array_reduce($request->lines, function ($acc, $item) {
                return $acc + $item['amount'];
            }, 0);

            $service->updateShopHeads(
                $date, $request->user(), $amount, $this->paymentType[$request->paymentType],  ''
            );

            DB::connection('firebird')->commit();
        } catch (Exception $e) {
            DB::connection('firebird')->rollBack();
            throw new ApiException($e->getMessage());
        }
    }
}
