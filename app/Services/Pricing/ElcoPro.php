<?php


namespace App\Services\Pricing;


use App\Entry;
use App\Good;
use App\Http\Resources\SellerPriceResource;
use App\SellerGood;
use App\SellerPrice;
use App\SellerWarehouse;
use App\Warehouse;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Str;

class ElcoPro
{
    private function make(Good $good, float $price, int $minQuantity, int $maxQuantity, bool $isInput): SellerPrice
    {
        $sellerGood= new SellerGood([
            'name' => $good->name->NAME,
            'producer' => $good->PRODUCER,
            'case' => $good->BODY,
            'code' => $good->GOODSCODE,
            'seller_id' => config('pricing.ElcoPro.sellerId'),
            'good_id' => $good->GOODSCODE,
            'basic_delivery_time' => config('pricing.ElcoPro.basicDeliveryTime'),
            'remark' => $good->DESCRIPTION,
            'packageQuantity' => 1,
        ]);
        $sellerWarehouse = new SellerWarehouse([
            'sellerGood' => $sellerGood,
            'quantity' => $good->quantity,
            'additional_delivery_time' => 0,
            'multiplicity' => 1,
            'remark' => '',
            'options' => null,
        ]);
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

    public function __invoke(string $search): ResourceCollection
    {
        //$sellerId = config('pricing.ElcoPro.sellerId');
        $goods = Good::with('name', 'retailPrice')
            ->where('HIDDEN', 0)
            ->whereHas('goodNames', function (Builder $query) use ($search) {
                return $query->where('NAME', 'CONTAINING', $search);
            })
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
            ])
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
                $this->make(
                    $good, $good->retailPrice->PRICEOPT, $good->retailPrice->QUANOPT, 0, false
                );
            }
        }
        return SellerPriceResource::collection($ret);
    }
}
