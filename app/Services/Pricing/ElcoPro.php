<?php


namespace App\Services\Pricing;


use App\Entry;
use App\Good;
use App\RetailStore;
use App\SellerGood;
use App\SellerPrice;
use App\SellerWarehouse;
use App\Warehouse;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ElcoPro
{
    private function make(Good $good, float $price, int $minQuantity, int $maxQuantity, bool $isInput): SellerPrice
    {
        $sellerGood = SellerGood::query()->firstOrNew([
            'seller_id' => config('pricing.ElcoPro.sellerId'),
            'good_id' => $good->GOODSCODE,
        ]);
        $sellerGood->fill([
            'name' => $good->name->NAME,
            'producer' => $good->PRODUCER,
            'case' => $good->BODY,
            'code' => $good->GOODSCODE,
            'basic_delivery_time' => config('pricing.ElcoPro.basicDeliveryTime'),
            'remark' => $good->DESCRIPTION,
            'package_quantity' => 1,
        ]);
        $sellerGood->save();
        $sellerWarehouse = SellerWarehouse::query()->firstOrNew(['seller_good_id' => $sellerGood->id]);
        $sellerWarehouse->fill([
            'quantity' => $good->quantity,
            'additional_delivery_time' => 0,
            'multiplicity' => 1,
            'remark' => '',
            'options' => null,
        ]);
        $sellerWarehouse->save();
        $sellerWarehouse->selerGood = $sellerGood;
        return new SellerPrice([
            'id' => Str::uuid(),
            'sellerWarehouse' => $sellerWarehouse,
            'min_quantity' => $minQuantity,
            'max_quantity' => $maxQuantity,
            'value' => $price,
            'CharCode' => 'RUB',
            'is_input' => $isInput,
            'updated_at' => Carbon::now(),
        ]);
    }

    public function __invoke(string $search): Collection
    {
        //$sellerId = config('pricing.ElcoPro.sellerId');
        $goods = Good::with('name', 'retailPrice')
            ->where('HIDDEN', 0)
            ->whereHas('goodNames', function (Builder $query) use ($search) {
                return $query->where('NAME', 'CONTAINING', $search);
            })
            ->when(!env('IS_ELECTRONICA', false), function (Builder $q) {
                return $q
                    ->whereHas('warehouse', function (Builder $query) {
                        return $query->where('QUAN', '>', 0);
                    })
                    ->addSelect([
                        'quantity' => Warehouse::query()
                            ->whereColumn('GOODSCODE', 'GOODS.GOODSCODE')
                            ->selectRaw('coalesce(sum(QUAN), 0)'),
                        'price' => Entry::query()
                            ->whereColumn('GOODSCODE', 'GOODS.GOODSCODE')
                            ->whereNotNull('SKLADINCODE')
                            ->select('PRICE')
                            ->orderByDesc('DATA')
                            ->take(1)
                    ]);
            })
            ->when(env('IS_ELECTRONICA', false), function (Builder $q) {
                return $q
                    ->whereHas('retailStore', function (Builder $query) {
                        return $query->where('QUAN', '>', 0);
                    })
                    ->addSelect([
                        'quantity' => RetailStore::query()
                            ->whereColumn('GOODSCODE', 'GOODS.GOODSCODE')
                            ->selectRaw('coalesce(sum(QUAN), 0)'),
                        'price' => Entry::query()
                            ->whereColumn('GOODSCODE', 'GOODS.GOODSCODE')
                            ->whereNotNull('SKLADINCODE')
                            ->select('PRICE')
                            ->orderByDesc('DATA')
                            ->take(1)
                    ]);
            })
            //->whereHas('warehouse', function (Builder $query) {
            //    return $query->where('QUAN', '>', 0);
            //})
            ->take(100)
            ->get();
        $ret = collect();
        foreach ($goods as $good) {
            $ret->push($this->make($good, $good->price, 1, 0, true));
            if ($good->retailPrice && !empty($good->retailPrice->PRICEROZN)) {
                $maxQuantity = 0;
                if (!empty($good->retailPrice->QUANMOPT) && $good->retailPrice->QUANMOPT > 1) {
                    $maxQuantity = $good->retailPrice->QUANMOPT - 1;
                }
                $ret->push($this->make($good, $good->retailPrice->PRICEROZN, 1, $maxQuantity, false));
            }
            if ($good->retailPrice
                &&
                !empty($good->retailPrice->PRICEMOPT)
                &&
                !empty($good->retailPrice->QUANMOPT)
            ) {
                $maxQuantity = 0;
                if (
                    !empty($good->retailPrice->QUANOPT)
                    &&
                    $good->retailPrice->QUANOPT > $good->retailPrice->QUANMOPT
                ) {
                    $maxQuantity = $good->retailPrice->QUANOPT - 1;
                }
                $ret->push(
                    $this->make(
                        $good, $good->retailPrice->PRICEMOPT, $good->retailPrice->QUANMOPT, $maxQuantity, false
                    )
                );
            }
            if ($good->retailPrice
                &&
                !empty($good->retailPrice->PRICEOPT)
                &&
                !empty($good->retailPrice->QUANOPT)
            ) {
                $ret->push(
                    $this->make(
                        $good, $good->retailPrice->PRICEOPT, $good->retailPrice->QUANOPT, 0, false
                    )
                );
            }
        }
        return $ret;
    }
}
