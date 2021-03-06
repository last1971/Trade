<?php


namespace App\Services\Pricing;

use App\SellerGood;
use App\SellerPrice;
use App\SellerWarehouse;
use App\Services\CompelApiService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class Compel
{
    /**
     * @param string $search
     * @return Collection
     * @throws \App\Exceptions\CompelException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function __invoke(string $search): Collection
    {
        $sellerId = config('pricing.Compel.sellerId');
        $compel = new CompelApiService();
        $ret = collect();
        foreach ($compel->searchByName($search)->result->items as $item) {
            $sellerGood = SellerGood::query()
                ->firstOrNew(['code' => $item->item_id, 'seller_id' => $sellerId
                ]);
            $packageQuantity = intval($item->qty_in_pack);
            $packageQuantity = $packageQuantity > intval($sellerGood->package_quantity)
                ? $packageQuantity
                : (intval($sellerGood->package_quantity) > 0 ? $sellerGood->package_quantity : 1);
            $sellerGood->fill([
                'name' => $item->item_name,
                'producer' => $item->item_brend,
                'case' => empty($item->package_name) ? $sellerGood->case : $item->package_name,
                'is_active' => true,
                'basic_delivery_time' => config('pricing.Compel.basicDeliveryTime'),
                'package_quantity' => $packageQuantity,
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
        return $ret;
    }
}
