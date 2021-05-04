<?php


namespace App\Services\Pricing;


use App\SellerPrice;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;


class DataBase
{

    public function __invoke(string $search): Collection
    {
        $sellerId = request('sellerId') ?? 0;
        request()->merge(['isFile' => 'true']);
        return SellerPrice::with('sellerWarehouse.sellerGood')
            ->whereHas('sellerWarehouse.sellerGood', function (Builder $query) use ($search, $sellerId) {
                return $query->where('is_active', true)
                    ->where('seller_id', $sellerId)
                    ->where('search_name', 'like', '%' . $search . '%');
            })
            ->take(100)
            ->get();
    }
}
