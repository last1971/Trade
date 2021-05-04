<?php


namespace App\Services\Pricing;


use App\SellerGood;
use App\SellerPrice;
use App\SellerWarehouse;
use App\Services\PromelecApiService;
use Illuminate\Support\Collection;

class Promelec
{
    public function __invoke(string $search): Collection
    {
        $sellerId = config('pricing.Promelec.sellerId');
        $promelec = new PromelecApiService();
        $ret = collect();
        foreach ($promelec->searchByName($search) as $item) {
            $sellerGood = SellerGood::query()
                ->firstOrNew(['code' => $item->item_id, 'seller_id' => $sellerId]);
            $sellerGood->fill([
                'name' => $item->name,
                'producer' => $item->producer_name ?? null,
                'case' => $item->package ?? null,
                'is_active' => true,
                'basic_delivery_time' => config('pricing.Promelec.basicDeliveryTime'),
                'package_quantity' => $item->pack_quant ?? 1,
                'remark' => $item->description ?? null,
            ]);
            $sellerGood->save();
            $sellerWarehouse = SellerWarehouse::query()->firstOrNew(['seller_good_id' => $sellerGood->id]);
            $sellerWarehouse->fill([
                'quantity' => $item->quant,
                'additional_delivery_time' => 0,
                'multiplicity' => $item->price_unit ?? 1,
                'remark' => '',
                'options' => null,
            ]);
            $sellerWarehouse->save();
            $sellerWarehouse->sellerGood = $sellerGood;
            $sellerWarehouse->sellerPrices()->delete();
            foreach ($item->pricebreaks ?? [] as $index => $pricebreak) {
                $minQuantity = $item->moq > $pricebreak->quant ? $item->moq : $pricebreak->quant;
                $maxQuantity = $index + 1 === count($item->pricebreaks)
                    ? 0
                    : $item->pricebreaks[$index + 1]->quant - 1;
                $sellerPrice = SellerPrice::query()->create([
                    'seller_warehouse_id' =>  $sellerWarehouse->id,
                    'min_quantity' => $minQuantity,
                    'max_quantity' => $maxQuantity,
                    'value' => $pricebreak->price / $item->price_unit,
                    'CharCode' => 'RUB',
                    'is_input' => true,
                ]);
                $sellerPrice->sellerWarehouse = $sellerWarehouse;
                $ret->push($sellerPrice);
                $sellerPrice = SellerPrice::query()->create([
                    'seller_warehouse_id' =>  $sellerWarehouse->id,
                    'min_quantity' => $minQuantity,
                    'max_quantity' => $maxQuantity,
                    'value' => $pricebreak->pureprice / $item->price_unit,
                    'CharCode' => 'RUB',
                    'is_input' => false,
                ]);
                $sellerPrice->sellerWarehouse = $sellerWarehouse;
                $ret->push($sellerPrice);
            }
            foreach ($item->vendors ?? [] as $vendor) {
                $sellerWarehouse = SellerWarehouse::query()
                    ->firstOrNew(['seller_good_id' => $sellerGood->id, 'code' => $vendor->vendor]);
                $sellerWarehouse->fill([
                    'quantity' => $vendor->quant,
                    'additional_delivery_time' => $vendor->delivery,
                    'multiplicity' => $vendor->mpq ?? 1,
                    'remark' => '',
                    'options' => [
                        'location_id' => $vendor->delivery === 2 ? 'М А Г А З И Н' : 'Л А Б А З',
                    ],
                ]);
                $sellerWarehouse->save();
                $sellerWarehouse->sellerGood = $sellerGood;
                $sellerWarehouse->sellerPrices()->delete();
                foreach ($vendor->pricebreaks as $index => $pricebreak) {
                    $minQuantity = $vendor->moq > $pricebreak->quant ? $vendor->moq : $pricebreak->quant;
                    $maxQuantity = $index + 1 === count($vendor->pricebreaks)
                        ? 0
                        : $vendor->pricebreaks[$index + 1]->quant - 1;
                    $sellerPrice = SellerPrice::query()->create([
                        'seller_warehouse_id' =>  $sellerWarehouse->id,
                        'min_quantity' => $minQuantity,
                        'max_quantity' => $maxQuantity,
                        'value' => $pricebreak->price,
                        'CharCode' => 'RUB',
                        'is_input' => true,
                    ]);
                    $sellerPrice->sellerWarehouse = $sellerWarehouse;
                    $ret->push($sellerPrice);
                    if (!empty($pricebreak->pureprice)) {
                        $sellerPrice = SellerPrice::query()->create([
                            'seller_warehouse_id' => $sellerWarehouse->id,
                            'min_quantity' => $minQuantity,
                            'max_quantity' => $maxQuantity,
                            'value' => $pricebreak->pureprice,
                            'CharCode' => 'RUB',
                            'is_input' => false,
                        ]);
                        $sellerPrice->sellerWarehouse = $sellerWarehouse;
                        $ret->push($sellerPrice);
                    }
                }
            }
        }
        return $ret;
    }
}
