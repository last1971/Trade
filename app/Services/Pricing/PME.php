<?php


namespace App\Services\Pricing;


use App\Jobs\ProcessUpdateSellerPrices;
use App\SellerGood;
use App\SellerPrice;
use App\SellerWarehouse;
use App\Services\PMEApiService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Ramsey\Uuid\Uuid;

class PME
{
    public function __invoke(string $search, array $exclude = []): Collection
    {
        $mouser = [];
        try {
            $opts = array('http' =>
                array(
                    'method' => 'POST',
                    'header' => 'Content-Type: application/soap+xml; charset=utf-8; action="http://api.mouser.com/service/SearchByKeyword"',
                    'content' => '<?xml version="1.0" encoding="UTF-8"?>
                <env:Envelope xmlns:env="http://www.w3.org/2003/05/soap-envelope" xmlns:ns1="http://api.mouser.com/service">
                <env:Header><ns1:MouserHeader><ns1:AccountInfo><ns1:PartnerID>' . env('MOUSER_CLIENT_CODE') . '</ns1:PartnerID>
                </ns1:AccountInfo></ns1:MouserHeader></env:Header><env:Body><ns1:SearchByKeyword><ns1:keyword>' . urlencode($search) . '</ns1:keyword>
                <ns1:records>50</ns1:records><ns1:startingRecord>0</ns1:startingRecord><ns1:searchOptions>4</ns1:searchOptions></ns1:SearchByKeyword></env:Body></env:Envelope>'
                )
            );
            $context = stream_context_create($opts);
            $result = file_get_contents('http://api.mouser.com/service/searchapi.asmx', false, $context);
            $result = str_replace("soap:", "", $result);
            $r = simplexml_load_string($result);

            foreach ($r->Body->SearchByKeywordResponse->SearchByKeywordResult->Parts->MouserPart as $el) {
                $mouser[(string)$el->MouserPartNumber] = [];
                foreach ($el->PriceBreaks->Pricebreaks as $price) {
                    $mouser[(string)$el->MouserPartNumber][(int)$price->Quantity] = str_replace('$', '', $price->Price);
                }
            }
        } catch (Exception $e) {
            Log::error('Mouser error ' . $e->getMessage());
        }
        $service = new PMEApiService(
            env('PME_CLIENT_CODE'),
            Storage::disk('local')->path('pmelpublic.pem')
        );
        $prices = $service->findByPartNumber($search,false);
        // dd($prices);
        $ret = collect();
        if (empty($prices->items->partNumbers)) return $ret;
        $partNumbers = is_array($prices->items->partNumbers)
            ? $prices->items->partNumbers
            : [$prices->items->partNumbers];
        // Log::info("PartNumbers", $partNumbers);
        foreach ($partNumbers as $partNumber) {
            $quantity = intval($partNumber->storeQty);
            $additionalDeliveryTime = 0;
            if ($quantity === 0 && !empty($partNumber->transitQty)) {
                $quantity = intval($partNumber->transitQty);
                $additionalDeliveryTime = Carbon::now()->diffInDays($partNumber->transitDate);
            }
            if (empty($partNumber->priceList->prices) || $quantity === 0) continue;
            $sellerGood = SellerGood::query()
                ->firstOrNew([
                    'code' => $partNumber->mouserPartNumber, 'seller_id' => config('pricing.PME.sellerId')
                ]);
            $packageQuantity = 1;
            $case = null;
            if (!empty($partNumber->specificationList->specifications)) {
                foreach ($partNumber->specificationList->specifications as $specification) {
                    if ($specification->key === 'Размер фабричной упаковки') {
                        $packageQuantity = (int) $specification->value;
                    }
                    if ($specification->key === 'Упаковка / блок') {
                        $case = $specification->value;
                    }
                }
            }
            $sellerGood->fill([
                'name' => $partNumber->partNumber,
                'producer' => $partNumber->manufacturer,
                'case' => $case,
                'is_active' => true,
                'basic_delivery_time' => config('pricing.PME.basicDeliveryTime'),
                'package_quantity' => $packageQuantity,
                'remark' => $partNumber->eccn ?? null . ' ' . $partNumber->description ?? null,
            ]);
            if ($sellerGood->isDirty()) $sellerGood->save();

            $sellerWarehouse = SellerWarehouse::query()->firstOrNew(['seller_good_id' => $sellerGood->id]);
            $sellerWarehouse->fill([
                'quantity' => $quantity,
                'additional_delivery_time' => $additionalDeliveryTime,
                'multiplicity' => $partNumber->mult ?? 1,
                'remark' => '',
                'options' => $partNumber->specificationList ?? null,
            ]);
            if ($sellerWarehouse->isDirty()) $sellerWarehouse->save();
            $sellerWarehouse->sellerGood = $sellerGood;
            // $sellerWarehouse->sellerPrices()->delete();
            $sellerPrices = collect();
            $itemPrices = is_array($partNumber->priceList->prices)
                ? $partNumber->priceList->prices
                : [$partNumber->priceList->prices];
            $pricesCount = count($itemPrices);
            for ($i = 0; $i < $pricesCount; $i++) {
                $sellerPrice = new SellerPrice();
                $sellerPrice->fill([
                    'id' => Uuid::uuid4()->toString(),
                    'seller_warehouse_id' => $sellerWarehouse->id,
                    'min_quantity' => intval($itemPrices[$i]->quantity),
                    'max_quantity' => $i < $pricesCount - 1
                        ? intval($itemPrices[$i + 1]->quantity) - 1
                        : 0,
                    'value' => (float) $itemPrices[$i]->amount,
                    'CharCode' => $itemPrices[$i]->currency,
                    'is_input' => true,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
                // $sellerPrice->save();
                $sellerPrices->push(clone $sellerPrice);
                $sellerPrice->sellerWarehouse = $sellerWarehouse;
                $ret->push($sellerPrice);

                if (!empty($mouser[$partNumber->mouserPartNumber][$itemPrices[$i]->quantity])) {
                    $sellerPrice = new SellerPrice();
                    $sellerPrice->fill([
                        'id' => Uuid::uuid4()->toString(),
                        'seller_warehouse_id' => $sellerWarehouse->id,
                        'min_quantity' => intval($itemPrices[$i]->quantity),
                        'max_quantity' => $i < $pricesCount - 1
                            ? intval($itemPrices[$i + 1]->quantity) - 1
                            : 0,
                        'value' => (float)
                            $mouser[$partNumber->mouserPartNumber][$itemPrices[$i]->quantity],
                        'CharCode' => $itemPrices[$i]->currency,
                        'is_input' => false,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ]);
                    // $sellerPrice->save();
                    $sellerPrices->push(clone $sellerPrice);
                    $sellerPrice->sellerWarehouse = $sellerWarehouse;
                    $ret->push($sellerPrice);
                }
            }
            ProcessUpdateSellerPrices::dispatch($sellerPrices, $sellerWarehouse, $exclude);
        }
        return $ret;
    }
}
