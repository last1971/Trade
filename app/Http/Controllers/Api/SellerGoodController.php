<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SellerGoodRequest;
use App\SellerGood;

class SellerGoodController extends Controller
{
    public function update(SellerGood $sellerGood, SellerGoodRequest $request)
    {
        $goodId = $request->validated()['goodId'];
        $sellerGood->fill(['good_id' => $goodId]);
        $sellerGood->save();
        return $sellerGood;
    }
}
