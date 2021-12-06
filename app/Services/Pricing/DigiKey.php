<?php


namespace App\Services\Pricing;


use App\Jobs\ProcessUpdateSellerPrices;
use App\SellerGood;
use App\SellerPrice;
use App\SellerWarehouse;
use App\Services\DigiKeyApiService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Ramsey\Uuid\Uuid;

class DigiKey
{
    private const SupplierDevicePackageId = 1291;

    private const PackageCaseId = 16;

    public function __invoke(string $search, array $exclude): Collection
    {
        $service = new DigiKeyApiService();
        $res = $service->keywordSearchWithRefreshToken($search);
        return collect($res['Products'])
            ->map(function ($item) use ($exclude) {
                $sellerGood = SellerGood::query()
                    ->firstOrNew([
                        'code' => $item['DigiKeyPartNumber'], 'seller_id' => config('pricing.DigiKey.sellerId')
                    ]);
                $sellerGood->fill([
                    'name' => $item['ManufacturerPartNumber'],
                    'producer' => !empty($item['Manufacturer']) ? $item['Manufacturer']['Value'] : null,
                    'case' => !empty($item['Parameters']) ? $this->getCase($item['Parameters']) : null,
                    'is_active' => true,
                    'basic_delivery_time' => config('pricing.DigiKey.basicDeliveryTime'),
                    'package_quantity' => $item['MinimumOrderQuantity'],
                    'remark' => $item['ExportControlClassNumber'] . ' ' . $item['ProductDescription'],
                ]);
                if ($sellerGood->isDirty()) $sellerGood->save();

                $sellerWarehouse = SellerWarehouse::query()->firstOrNew(['seller_good_id' => $sellerGood->id]);
                $sellerWarehouse->fill([
                    'quantity' => intval($item['QuantityAvailable']),
                    'additional_delivery_time' => 0,
                    'multiplicity' => $item['MinimumOrderQuantity'],
                    'remark' => '',
                    'options' => $item['Parameters'],
                ]);
                if ($sellerWarehouse->isDirty()) $sellerWarehouse->save();
                $sellerWarehouse->sellerGood = $sellerGood;
                // $sellerWarehouse->sellerPrices()->delete();
                $pricesCount = count($item['StandardPricing']);

                $extraCharge = 1 + (env('DIGIKEY_EXTRA_CHARGE', 50) / 100);

                $sellerPrices = collect();

                $res = collect($item['StandardPricing'])->map(
                    function ($price, $index)
                    use ($sellerWarehouse, $pricesCount, $item, $extraCharge, $exclude, $sellerPrices) {
                        $sellerPrice = new SellerPrice([
                            'id' => Uuid::uuid4()->toString(),
                            'seller_warehouse_id' => $sellerWarehouse->id,
                            'min_quantity' => intval($price['BreakQuantity']),
                            'max_quantity' => $index + 1 === $pricesCount
                                ? 0
                                : intval($item['StandardPricing'][$index + 1]['BreakQuantity']) - 1,
                            'value' => (float)$price['UnitPrice'] * $extraCharge,
                            'CharCode' => 'USD',
                            'is_input' => true,
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now(),
                        ]);
                        // $sellerPrice->save();
                        $sellerPrices->push(clone $sellerPrice);
                        $sellerPrice->sellerWarehouse = $sellerWarehouse;
                        return $sellerPrice;
                    }
                );
                ProcessUpdateSellerPrices::dispatch($sellerPrices, $sellerWarehouse, $exclude);
                return $res;
            })
            ->collapse();
    }

    private function getCase(array $parameters): ?string
    {
        foreach ($parameters as $parameter) {
            if (
                $parameter['ParameterId'] === self::SupplierDevicePackageId
                || $parameter['ParameterId'] === self::PackageCaseId
            ) {
                return $parameter['Value'];
            }
        }
        return null;
    }
}
