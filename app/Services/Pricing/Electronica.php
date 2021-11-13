<?php

namespace App\Services\Pricing;

use App\SellerGood;
use App\SellerPrice;
use App\SellerWarehouse;
use GuzzleHttp\Client;
use Illuminate\Support\Collection;

class Electronica
{
    /**
     * @param string $search
     * @return Collection
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function __invoke(string $search): Collection
    {
        $client = new Client();

        $headers = [
            'Authorization' => 'Bearer ' . env('ELECTRONICA_TOKEN', '123'),
            'Accept'        => 'application/json',
        ];

        $response = $client->get('https://electronica.su/api/seller-price', [
            'headers' => $headers,
            'query' => ['sellerId' => 0, 'search' => $search],
        ]);

        $result = json_decode($response->getBody()->getContents());

        $sellerId = config('pricing.Electronica.sellerId');

        $sellerGoods = collect();

        $ret = collect();

        foreach ($result->data as $electronicaPrice) {
            $sellerGood = SellerGood::query()
                ->firstOrNew(['code' => $electronicaPrice->code, 'seller_id' => $sellerId]);
            $sellerGood->fill([
                'name' => $electronicaPrice->name,
                'producer' => $electronicaPrice->producer,
                'case' => $electronicaPrice->case,
                'is_active' => true,
                'basic_delivery_time' => config('pricing.Electronica.basicDeliveryTime'),
                'package_quantity' => $electronicaPrice->packageQuantity,
            ]);
            $sellerGood->save();

            $sellerWarehouse = SellerWarehouse::query()->firstOrNew(['seller_good_id' => $sellerGood->id]);
            $sellerWarehouse->fill([
                'quantity' => $electronicaPrice->quantity,
                'additional_delivery_time' => 0,
                'multiplicity' => $electronicaPrice->multiplicity,
                'remark' => $electronicaPrice->remark,
                'options' => [
                    'location_id' => 'М А Г А З И Н',
                ],
            ]);
            $sellerWarehouse->save();
            $sellerWarehouse->sellerGood = $sellerGood;

            if (!$sellerGoods->contains($electronicaPrice->code)) {
                $sellerGoods->push($electronicaPrice->code);
                $sellerWarehouse->sellerPrices()->delete();
            }
            $sellerPrice = new SellerPrice([
                'min_quantity' => $electronicaPrice->minQuantity,
                'max_quantity' => $electronicaPrice->maxQuantity,
                'value' => $electronicaPrice->price,
                'CharCode' => $electronicaPrice->CharCode,
                'is_input' => $electronicaPrice->isInput,
            ]);
            $sellerWarehouse->sellerPrices()->save($sellerPrice);
            $sellerPrice->sellerWarehouse = $sellerWarehouse;

            $ret->push($sellerPrice);
        }

        return $ret;
    }

}
