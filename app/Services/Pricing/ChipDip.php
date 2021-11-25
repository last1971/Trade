<?php


namespace App\Services\Pricing;


use App\SellerGood;
use App\SellerPrice;
use App\SellerWarehouse;
use Illuminate\Support\Collection;
use Last1971\ChipDipParser\ChipDipParser;

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

    public function __invoke(string $search): Collection
    {
        $sellerId = config('pricing.ChipDip.sellerId');
        $chip = new ChipDipParser();
        $lines = collect($chip->searchByName($search));
        return $lines->map(function (array $line) use ($sellerId) {
            $sellerGood = SellerGood::query()->firstOrNew(['code' => $line['code'], 'seller_id' => $sellerId]);
            $sellerGood->fill([
                'name' => $line['name'],
                'producer' => $line['producer'],
                'is_active' => true,
                'basic_delivery_time' => config('pricing.ChipDip.basicDeliveryTime'),
                'package_quantity' => $line['multiple'],
            ]);
            $sellerGood->save();
            $sellerGood->sellerWarehouses()->update(['quantity' => 0]);

            $quantities = collect($line['quantities']);
            return $quantities
                ->map(function (array $quantity) use ($sellerGood, $line) {
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
                    $sellerWarehouse->save();
                    $sellerWarehouse->sellerGood = $sellerGood;
                    $sellerWarehouse->sellerPrices()->delete();

                    $prices = collect($line['prices']);
                    $pricesCount = $prices->count();
                    return $prices
                        ->map(function (array $price, $index) use ($sellerWarehouse, $pricesCount, $line) {
                            $sellerPrice = new SellerPrice([
                                'seller_warehouse_id' => $sellerWarehouse->id,
                                'min_quantity' => (int) $price['min'],
                                'max_quantity' => $index + 1 === $pricesCount
                                    ? 0
                                    : $line['prices'][$index + 1]['min'] - 1,
                                'value' => (float) $price['price'],
                                'CharCode' => $price['valute'],
                                'is_input' => true,
                            ]);
                            $sellerPrice->save();
                            $sellerPrice->sellerWarehouse = $sellerWarehouse;
                            return $sellerPrice;
                        });
                })
                ->collapse();
        })
        ->collapse();
    }
}
