<?php

namespace App\Http\Controllers\Api;

use App\Buyer;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\GoodsListRequest;
use App\Services\AtolService;
use App\Services\EFTPOSService;
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

    public function store(GoodsListRequest $request, GoodsListService $service, AtolService $atol, EFTPOSService $etf)
    {
        $service->isLocalTransaction();
        $amount = array_reduce($request->lines, function ($acc, $item) {
            return $acc + $item['amount'];
        }, 0);
        $urn = '';
        $l4d = '';

        try {
            $date = $service->sale($request->lines, $request->user(), $request->buyerId);

            if ($request->paymentType === 'electronically') {
                $sale = $etf->sale($amount);
                $urn = $sale['urn'];
                $l4d = $sale['l4d'];
            }

            if ($request->paymentType !== 'black') {
                $atol->operator = $request->user();
                $atol->receipt(
                    $request->lines,
                    'sell',
                    $request->paymentType,
                    Buyer::find($request->buyerId),
                );
            }

            $service->updateShopHeads(
                $date,
                $request->user(),
                $amount,
                $request->paymentType === 'electronically' ? $urn : $this->paymentType[$request->paymentType],
                $l4d
            );

            DB::connection('firebird')->commit();
        } catch (Exception $e) {
            DB::connection('firebird')->rollBack();
            if ($urn !== '') {
                $etf = new EFTPOSService();
                $etf->reversalOfSale($urn, $amount, 0);
            }
            throw new ApiException($e->getMessage());
        }
    }
}
