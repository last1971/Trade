<?php

namespace App\Services\Pricing;

use App\SellerGood;
use App\SellerPrice;
use App\SellerWarehouse;
use App\Services\ElitanService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;
use stdClass;

class Elitan
{
    private function processPrices(Collection &$ret, Model $sellerWarehouse, stdClass $stock): void
    {
        $prices = $sellerWarehouse->sellerPrices;

        $sellerWarehouse->sellerPrices()->delete();

        foreach (array_reverse($stock->price) as $index => $price) {
            $minQuantity = $price->count;

            $maxQuantity = $index + 1 === count($stock->price)
                ? 0
                : $stock->price[$index + 1]->count - 1;

            $extraCharge = 1 + (env('ELITAN_EXTRA_CHARGE', 10) / 100);

            $sellerPrice = SellerPrice::query()->create([
                'id' => Uuid::uuid4()->toString(),
                'seller_warehouse_id' =>  $sellerWarehouse->id,
                'min_quantity' => (int) $minQuantity,
                'max_quantity' => (int) $maxQuantity,
                'value' => $price->price * $extraCharge,
                'CharCode' => 'RUB',
                'is_input' => true,
                'updated_at' => Carbon::now(),
            ]);
            $sellerPrice->sellerWarehouse = $sellerWarehouse;
            $ret->push($sellerPrice);
            $prices->push($sellerPrice);
        }
        // Log::info('Prices', $prices->toArray());
    }

    private function processStocks(Collection &$ret, Model $sellerGood, stdClass $data): void
    {
        foreach ($data->stock as $stock) {
            $quantity = (int) $stock->stock;
            if ($stock->blitz === '1' || $quantity < 0) continue;
            $sellerWarehouse = SellerWarehouse::query()
                ->firstOrNew(['seller_good_id' => $sellerGood->id, 'code' => $stock->id_stock]);
            $additionalDeliveryTime = (int) $stock->term_delay;
            $locationId = 'R U S S I A';
            if (trim($stock->term_delay_string) === 'сегодня') {
                $additionalDeliveryTime = 0;
                $locationId = 'С К Л А Д';
            }
            if ($additionalDeliveryTime > 5) {
                $additionalDeliveryTime *= 2;
                $locationId = 'Л А Б А З';
            }
            $sellerWarehouse->fill([
                'quantity' => (int) $quantity,
                'additional_delivery_time' => $additionalDeliveryTime,
                'multiplicity' => (int) ($stock->normoupakovka ?? 1),
                'options' => [
                    'location_id' => $locationId,
                ],
                'remark' => '',
            ]);
            if ($sellerWarehouse->isDirty()) $sellerWarehouse->save();
            $sellerWarehouse->sellerGood = $sellerGood;
            $this->processPrices($ret, $sellerWarehouse, $stock);
        }
    }

    public function __invoke(string $search): Collection
    {
        $service = new ElitanService();
        $response = $service->search($search);
        $sellerId = config('pricing.Elitan.sellerId');
        $ret = collect();
        foreach ($response->items->data ?? [] as $data) {
            $sellerGood = SellerGood::query()
                ->firstOrNew(['code' => $data->Ntovara, 'seller_id' => $sellerId]);
            $nameProducer = explode('@', $data->partname);
            $packageQuantity = $data->stock[0]->pack ?? 1;
            $sellerGood->fill([
                'name' => $nameProducer[0],
                'producer' => empty($nameProducer[1]) ? '' : $nameProducer[1],
                'case' => $data->housing,
                'is_active' => true,
                'basic_delivery_time' => config('pricing.Compel.basicDeliveryTime'),
                'package_quantity' => $packageQuantity,
                'remark' => Str::limit($data->bignote, 395),
            ]);
            if ($sellerGood->isDirty()) $sellerGood->save();
            $this->processStocks($ret, $sellerGood, $data);
        }
        return $ret;
    }
}
