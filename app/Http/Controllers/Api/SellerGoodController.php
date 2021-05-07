<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SellerGoodRequest;
use App\SellerGood;
use Illuminate\Support\Facades\Cache;

class SellerGoodController extends Controller
{
    public function update(SellerGood $sellerGood, SellerGoodRequest $request)
    {
        $sellerGood->fill(['good_id' => $request->validated()['goodId']]);
        $sellerGood->save();
        Cache::tags($sellerGood->id)->flush();
        return $sellerGood;
    }
}
