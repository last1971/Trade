<?php

namespace App\Services\Pricing;

use App\Jobs\ProcessUpdateSellerPrices;
use App\SellerGood;
use App\SellerPrice;
use App\SellerWarehouse;
use App\Services\PlatanApiService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Ramsey\Uuid\Uuid;

class Platan
{
    public function __invoke(string $search, array $exclude): Collection
    {
        $ret = collect();
        $sellerId = config('pricing.Platan.sellerId');
        $service = new PlatanApiService();
        $retail = $service->searchByName($search);
        if (empty($retail->items)) return $ret;
        $retailCollection = collect();
        foreach ($retail->items as $item) {
            $retailCollection->put($item->NOM_N, $item);
        }
        $wholesale = $service->searchById($retailCollection->keys()->all());
        if (empty($wholesale->items)) return $ret;
        foreach ($wholesale->items as $item) {
            $quantity = intval($item->QUANTY);
            if ($quantity < 1) continue;

            $retailItem = $retailCollection->get($item->NOM_N);

            $sellerGood = SellerGood::query()
                ->firstOrNew(['code' => $item->NOM_N, 'seller_id' => $sellerId]);
            $sellerGood->fill([
                'name' => $item->NAME,
                'producer' => $item->MANUFAC,
                'case' => null,
                'is_active' => true,
                'basic_delivery_time' => config('pricing.Platan.basicDeliveryTime'),
                'package_quantity' => $item->QUANTY_PACK,
                'remark' => null,
            ]);
            if ($sellerGood->isDirty()) $sellerGood->save();

            $sellerWarehouse = SellerWarehouse::query()->firstOrNew(['seller_good_id' => $sellerGood->id]);
            $multiplicity = intval($item->KRATNOST);
            $sellerWarehouse->fill([
                'quantity' => $quantity,
                'additional_delivery_time' => 0,
                'multiplicity' => $multiplicity === 0 ? 1 : $multiplicity,
                'remark' => '',
                'options' => null,
            ]);
            if ($sellerWarehouse->isDirty()) $sellerWarehouse->save();

            $sellerPrices = collect();
            for ($i=1; $i<6; $i++) {
                $minSoSklada = empty($item->MINSOSKL) ? 1 : intval($item->MINSOSKL);
                $currentQuantity = intval($item->{'QUANTY_' . $i});
                $minQuantity = $minSoSklada > $currentQuantity ? $minSoSklada : $currentQuantity;
                $maxQuantity = $i === 5
                    ? 0
                    : intval($item->{'QUANTY_' . ($i + 1)}) - 1;
                $sellerPrice = new SellerPrice([
                    'id' => Uuid::uuid4()->toString(),
                    'seller_warehouse_id' =>  $sellerWarehouse->id,
                    'min_quantity' => $minQuantity,
                    'max_quantity' => $maxQuantity,
                    'value' => floatval($item->{'PRICE_' . $i}),
                    'CharCode' => 'RUB',
                    'is_input' => true,
                    'updated_at' => Carbon::now(),
                ]);
                $sellerPrices->push(clone $sellerPrice);
                $sellerPrice->sellerWarehouse = $sellerWarehouse;
                $ret->push($sellerPrice);

                $sellerPrice = new SellerPrice([
                    'id' => Uuid::uuid4()->toString(),
                    'seller_warehouse_id' =>  $sellerWarehouse->id,
                    'min_quantity' => $minQuantity,
                    'max_quantity' => $maxQuantity,
                    'value' => floatval($retailItem->{'PRICE_' . $i}),
                    'CharCode' => 'RUB',
                    'is_input' => false,
                    'updated_at' => Carbon::now(),
                ]);
                $sellerPrices->push(clone $sellerPrice);
                $sellerPrice->sellerWarehouse = $sellerWarehouse;
                $ret->push($sellerPrice);
            }
            ProcessUpdateSellerPrices::dispatch($sellerPrices, $sellerWarehouse, $exclude);
        }
        return $ret;
    }
}
