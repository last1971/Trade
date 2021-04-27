<?php


namespace App\Services\Pricing;


use App\Http\Resources\SellerPriceResource;
use App\SellerGood;
use App\SellerPrice;
use App\SellerWarehouse;
use App\Services\CompelApiService;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;

class Compel
{
    public function __invoke(string $search): ResourceCollection
    {
        $sellerId = config('pricing.Compel.sellerId');
        $compel = new CompelApiService();
        $ret = collect();
        foreach ($compel->apiSearchByName($search)->result->items as $item) {
            $sellerGood = SellerGood::query()
                ->firstOrNew(['code' => $item->item_id, 'seller_id' => $sellerId
                ]);
            $packageQuantity = intval($item->qty_in_pack);
            $sellerGood->fill([
                'name' => $item->item_name,
                'producer' => $item->item_brend,
                'case' => $item->package_name,
                'is_active' => true,
                'basic_delivery_time' => config('pricing.Compel.basicDeliveryTime'),
                'package_quantity' => $packageQuantity > 0 ? $packageQuantity : 1
            ]);
            $sellerGood->save();
            foreach ($item->proposals as $proposal) {
                /*
                 *  Количество для ДМС может браться из последней строчки прайса
                 */
                $quantity = $proposal->vend_qty;
                if ($quantity == 0 && count($proposal->price_qty) > 0) {
                    $quantity = $proposal->price_qty[count($proposal->price_qty) - 1]->max_qty;
                }
                $code = empty(trim($proposal->location_id))
                    ? $proposal->prognosis_id . ';' . $proposal->vend_type . ';' . $proposal->vend_qty . ';'
                    . $proposal->vend_note
                    : null;
                $sellerWarehouse = SellerWarehouse::query()
                    ->firstOrNew(['seller_good_id' => $sellerGood->id, 'code' => $code]);
                $sellerWarehouse->fill([
                    'quantity' => $quantity,
                    'additional_delivery_time' => $proposal->prognosis_days - 1,
                    'multiplicity' => $proposal->mpq,
                    'options' => $proposal,
                    'remark' => '',
                ]);
                $sellerWarehouse->save();
                $sellerWarehouse->sellerGood = $sellerGood;
                $sellerWarehouse->sellerPrices()->delete();
                if ($quantity > 0) {
                    foreach ($proposal->price_qty as $price) {
                        $sellerPrice = new SellerPrice();
                        $sellerPrice->fill([
                            'seller_warehouse_id' => $sellerWarehouse->id,
                            'min_quantity' => $price->min_qty,
                            'max_quantity' => $price->max_qty,
                            'value' => $price->price,
                            'CharCode' => 'USD',
                            'is_input' => true,
                            'created_at' => Carbon::now(),
                            'updated_at' => $proposal->location_id === 'CENTRE'
                                ? Carbon::now()
                                : Carbon::parse($proposal->vend_proposal_date)
                        ]);
                        $sellerPrice->save([ 'timestamps' =>  false ]);
                        $sellerPrice->sellerWarehouse = $sellerWarehouse;
                        $ret->push($sellerPrice);
                    }
                }
            }
        }
        return SellerPriceResource::collection($ret);
    }
}
