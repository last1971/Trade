<?php

namespace App\Services\Pricing;

use App\SellerGood;
use App\SellerPrice;
use App\SellerWarehouse;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Collection;
use Ramsey\Uuid\Uuid;

class GetChips
{
    public function __invoke(string $search, array $exclude): Collection
    {
        $sellerId = config('pricing.GetChips.sellerId');
        $ret = collect();
        $client = new Client();
        $response = $client->get(
            env('GETCHIPS_API_URL', 'http://sapi.getchips.ru/api/search'),
            [
                'query' => [
                    'token' => env('GETCHIPS_TOKEN', 'hw9ltitaqzedfy2uc8agb0epum9p86qq'),
                    'input_field' => $search,
                    'count_field' => 1,
                    'currency_code' => 1, // USD
                ]
            ]
        );
        $items = json_decode($response->getBody()->getContents());
        foreach ($items as $item) {
            $code = $item->donorID . '-' . $item->title;
            $sellerGood = SellerGood::query()->firstOrNew(['code' => $code, 'seller_id' => $sellerId]);
            $packageQuantity = intval($item->sPack);
            $sellerGood->fill([
                'name' => $item->title,
                'producer' => $item->brand,
                'is_active' => true,
                'basic_delivery_time' => config('pricing.GetChips.basicDeliveryTime'),
                'package_quantity' => $packageQuantity,
            ]);
            if ($sellerGood->isDirty()) $sellerGood->save();
            $sellerWarehouse = SellerWarehouse::query()
                ->firstOrNew(['seller_good_id' => $sellerGood->id, 'code' => $item->donorID]);
            $sellerWarehouse->fill([
                'quantity' => $item->quantity,
                'additional_delivery_time' => $item->orderdays,
                'multiplicity' => 1,
                'options' => [
                    'location_id' => $item->donor,
                ],
            ]);
            if ($sellerWarehouse->isDirty()) $sellerWarehouse->save();
            $sellerWarehouse->sellerGood = $sellerGood;
            $sellerPrices = collect();
            foreach ($item->priceBreak as $i => $priceBreak) {
                $sellerPrice = new SellerPrice();
                $sellerPrice->fill([
                    'id' => Uuid::uuid4()->toString(),
                    'seller_warehouse_id' => $sellerWarehouse->id,
                    'min_quantity' => $priceBreak->quantity,
                    'max_quantity' => $i + 1 === count($item->priceBreak) ? 0 : $item->priceBreak[$i + 1]->quantity - 1,
                    'value' => $priceBreak->price,
                    'CharCode' => 'USD',
                    'is_input' => true,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]);
                $sellerPrices->push(clone $sellerPrice);
                // $sellerPrice->save([ 'timestamps' =>  false ]);
                $sellerPrice->sellerWarehouse = $sellerWarehouse;
                $ret->push($sellerPrice);
            }
        }
        return $ret;
    }
}
