<?php


namespace App\Services\Pricing;


use App\SellerGood;
use App\SellerPrice;
use App\SellerWarehouse;
use App\Services\DigiKeyApiService;
use Illuminate\Support\Collection;

class DigiKey
{
    private const SupplierDevicePackageId = 1291;

    private const PackageCaseId = 16;

    public function __invoke(string $search): Collection
    {
        $service = new DigiKeyApiService();
        $res = $service->keywordSearchWithRefreshToken($search);
        return collect($res['Products'])
            ->map(function ($item) {
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
                $sellerGood->save();

                $sellerWarehouse = SellerWarehouse::query()->firstOrNew(['seller_good_id' => $sellerGood->id]);
                $sellerWarehouse->fill([
                    'quantity' => $item['QuantityAvailable'],
                    'additional_delivery_time' => 0,
                    'multiplicity' => $item['MinimumOrderQuantity'],
                    'remark' => '',
                    'options' => $item['Parameters'],
                ]);
                $sellerWarehouse->save();
                $sellerWarehouse->sellerGood = $sellerGood;
                $sellerWarehouse->sellerPrices()->delete();
                $pricesCount = count($item['StandardPricing']);

                return collect($item['StandardPricing'])->map(
                    function ($price, $index) use ($sellerWarehouse, $pricesCount, $item) {
                        $sellerPrice = new SellerPrice([
                            'seller_warehouse_id' => $sellerWarehouse->id,
                            'min_quantity' => $price['BreakQuantity'],
                            'max_quantity' => $index + 1 === $pricesCount
                                ? 0
                                : $item['StandardPricing'][$index + 1]['BreakQuantity'] - 1,
                            'value' => (float)$price['UnitPrice'] * env('DIGIKEY_COEFF', 1.5),
                            'CharCode' => 'USD',
                            'is_input' => true,
                        ]);
                        $sellerPrice->save();
                        $sellerPrice->sellerWarehouse = $sellerWarehouse;
                        return $sellerPrice;
                    }
                );
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
