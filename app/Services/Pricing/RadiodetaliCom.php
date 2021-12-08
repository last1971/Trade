<?php

namespace App\Services\Pricing;

use App\Jobs\ProcessUpdateSellerPrices;
use App\SellerGood;
use App\SellerPrice;
use App\SellerWarehouse;
use App\Services\PromelecApiService;
use App\Services\RadiodetaliComService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Ramsey\Uuid\Uuid;

class RadiodetaliCom
{
    public function __invoke(string $search, array $exclude): Collection
    {
        $service = new RadiodetaliComService();
        $items = $service->search($search);
        $sellerId = config('pricing.Radiodetali.sellerId');
        $ret = collect();
        foreach ($items as $item) {
            if (empty($item->partnum)) continue;
            $code = empty($item->itemid) ? $item->partnum : $item->itemid;
            $sellerGood = SellerGood::query()
                ->firstOrNew(['code' => $code, 'seller_id' => $sellerId]);
            $sellerGood->fill([
                'name' => $item->partnum,
                'producer' => empty($item->manf) ? '' : $item->manf,
                'case' => null,
                'is_active' => true,
                'basic_delivery_time' => config('pricing.Radiodetali.basicDeliveryTime'),
                'package_quantity' => 1,
                'remark' => empty($item->note) ? '' : $item->note,
            ]);
            if ($sellerGood->isDirty()) $sellerGood->save();

            $sellerWarehouse = SellerWarehouse::query()
                ->firstOrNew(['seller_good_id' => $sellerGood->id, 'code' => $item->id_stock]);
            $sellerWarehouse->fill([
                'quantity' => (int) $item->qty,
                'additional_delivery_time' => $item->dlv_days - 1,
                'multiplicity' => empty($item->p_rate) ? 1 : $item->p_rate,
                'remark' => '',
                'options' => [
                    'location_id' => $item->nm_stock,
                ],
            ]);
            if ($sellerWarehouse->isDirty()) $sellerWarehouse->save();
            $sellerWarehouse->sellerGood = $sellerGood;
            // $sellerWarehouse->sellerPrices()->delete();
            $sellerPrices = collect();
            foreach ($item->price_up5 as $index => $price) {
                $minQuantity = intval($price->min_qty);
                $maxQuantity = $index + 1 === count($item->price_up5)
                    ? 0
                    : intval($item->price_up5[$index + 1]->min_qty) - 1;
                $sellerPrice = new SellerPrice([
                    'id' => Uuid::uuid4()->toString(),
                    'seller_warehouse_id' =>  $sellerWarehouse->id,
                    'min_quantity' => $minQuantity,
                    'max_quantity' => $maxQuantity,
                    'value' => floatval($price->price),
                    'CharCode' => $item->curr,
                    'is_input' => true,
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
