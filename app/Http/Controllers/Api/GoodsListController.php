<?php

namespace App\Http\Controllers\Api;

use App\Buyer;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\GoodsListRequest;
use App\Services\AtolService;
use App\Services\GoodsListService;
use Illuminate\Support\Facades\DB;

class GoodsListController extends Controller
{
    //
    public function store(GoodsListRequest $request, GoodsListService $service, AtolService $atol)
    {
        $service->isLocalTransaction();
        try {
            $service->sale($request->lines, $request->user(), $request->buyerId);
            if ($request->paymentType !== 'black') {
                $atol->operator = $request->user();
                $atol->connect();
                $atol->receipt(
                    $request->lines,
                    'sell', $request->paymentType,
                    Buyer::find($request->buyerId),
                );
            }
            DB::connection('firebird')->commit();
        } catch (\Exception $e) {
            DB::connection('firebird')->rollBack();
            throw new ApiException($e->getMessage());
        }
    }
}
