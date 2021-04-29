<?php


namespace App\Services\Pricing;


use App\Http\Resources\SellerPriceResource;
use App\RuichiGood;
use Illuminate\Http\Resources\Json\ResourceCollection;

class Ruichi
{
    public function __invoke(string $search): ResourceCollection
    {
        $ruichiGoods = RuichiGood::query()
            ->where('tovmark1', 'like', '%' . $search . '%')
            ->where('fost', '>', 0)
            ->take(100)
            ->get();
        $ret = collect();
        foreach ($ruichiGoods as $ruichiGood) {
            $ret = $ret->merge($ruichiGood->sellerPrices());
        }
        return SellerPriceResource::collection($ret);
    }
}
