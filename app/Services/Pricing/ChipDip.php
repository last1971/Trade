<?php


namespace App\Services\Pricing;


use App\Jobs\ProcessUpdateSellerPrices;
use App\SellerGood;
use App\SellerPrice;
use App\SellerWarehouse;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Last1971\ChipDipParser\ChipDipParser;
use Ramsey\Uuid\Uuid;

class ChipDip
{
    /**
     * @param array $quantity
     * @return array
     */
    private function progonose(array $quantity): array
    {
        if (preg_match(' ~\-(\d{1,2}).*недел.*~', $quantity['reason'], $matches)) {
            return ['', 'Л А Б А З', $matches[1] * 7];
        }
        if (preg_match(' ~г\.([a-яА-Я-]*),.*\-(\d{1,2})~', $quantity['reason'], $matches)) {
            return $matches;
        }
        return ['', 'М А Г А З И Н', 0];
    }

    public function __invoke(string $search, array $exclude): Collection
    {
        $sellerId = config('pricing.ChipDip.sellerId');
        $chip = new ChipDipParser();
        $lines = collect($chip->searchByName($search));
        return $lines->map(function (array $line) use ($sellerId, $exclude) {
            $sellerGood = SellerGood::query()->firstOrNew(['code' => $line['code'], 'seller_id' => $sellerId]);
            $sellerGood->fill([
                'name' => $line['name'],
                'producer' => $line['producer'],
                'is_active' => true,
                'basic_delivery_time' => config('pricing.ChipDip.basicDeliveryTime'),
                'package_quantity' => $line['multiple'],
            ]);
            if ($sellerGood->isDirty()) $sellerGood->save();
            // $sellerGood->sellerWarehouses()->update(['quantity' => 0]);

            $quantities = collect($line['quantities']);
            return $quantities
                ->map(function (array $quantity) use ($sellerGood, $line, $exclude) {
                    $sellerWarehouse = SellerWarehouse::query()
                        ->firstOrNew(['seller_good_id' => $sellerGood->id, 'code' => $quantity['reason']]);

                    $prognose = $this->progonose($quantity);

                    $sellerWarehouse->fill([
                        'quantity' => (int) $quantity['quantity'],
                        'additional_delivery_time' => (int) $prognose[2],
                        'multiplicity' => (int) $line['multiple'],
                        'remark' => '',
                        'options' => [
                            'location_id' => $prognose[1],
                        ],
                    ]);
                    if ($sellerWarehouse->isDirty()) $sellerWarehouse->save();
                    $sellerWarehouse->sellerGood = $sellerGood;
                    // $sellerWarehouse->sellerPrices()->delete();

                    $prices = collect($line['prices']);
                    $pricesCount = $prices->count();
                    $sellerPrices = collect();
                    $res = $prices
                        ->map(
                            function (array $price, $index)
                            use ($sellerWarehouse, $pricesCount, $line, $exclude, $sellerPrices) {
                                $sellerPrice = new SellerPrice([
                                    'id' => Uuid::uuid4()->toString(),
                                    'seller_warehouse_id' => $sellerWarehouse->id,
                                    'min_quantity' => (int) $price['min'],
                                    'max_quantity' => $index + 1 === $pricesCount
                                        ? 0
                                        : $line['prices'][$index + 1]['min'] - 1,
                                    'value' => (float) $price['price'],
                                    'CharCode' => $price['valute'],
                                    'is_input' => true,
                                    'created_at' => Carbon::now(),
                                    'updated_at' => Carbon::now(),
                                ]);
                                //$sellerPrice->save();
                                $sellerPrices->push(clone $sellerPrice);
                                $sellerPrice->sellerWarehouse = $sellerWarehouse;
                                return $sellerPrice;
                            }
                        );
                    ProcessUpdateSellerPrices::dispatch($sellerPrices, $sellerWarehouse, $exclude);
                    return $res;
                })
                ->collapse();
        })
        ->collapse();
    }
}
