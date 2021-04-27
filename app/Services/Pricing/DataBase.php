<?php


namespace App\Services\Pricing;


use App\Http\Resources\SellerPriceResource;
use App\SellerPrice;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\ResourceCollection;

class DataBase
{
    public function __invoke(string $search, int $sellerId): ResourceCollection
    {
        $prices = SellerPrice::with('sellerWarehouse.sellerGood')
            ->whereHas('sellerWarehouse.sellerGood', function (Builder $query) use ($search, $sellerId) {
                return $query->where('is_active', true)
                    ->where('seller_id', $sellerId)
                    ->where('search_name', 'like', '%' . $search . '%');
            })
            ->take(100)
            ->get();
        return SellerPriceResource::collection($prices);
    }
}
