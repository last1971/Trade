<?php


namespace App\Services\Pricing;

use App\Jobs\ProcessUpdateSellerPrices;
use App\SellerGood;
use App\SellerPrice;
use App\SellerWarehouse;
use App\Services\CompelApiService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Uuid;

class Compel
{
    const CENTER = 0;

    const DMS = 1;

    protected $store = self::CENTER;

    private array $supplierTypes = [
        'CD'  => 'Каталожный дистрибьютор',
        'M'   => 'Производитель',
        'OD'  => 'Официальный дистрибьютор',
        'MIX' => 'Дистрибьютор со смешанной моделью',
        'ID'  => 'Независимый дистрибьютор',
        'MF'  => 'Франчайзинговый производитель',
    ];

    /**
     * @param string $search
     * @param array $explode
     * @return Collection
     * @throws \App\Exceptions\CompelException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function __invoke(string $search, array $explode): Collection
    {
        $sellerId = $this->store === self::CENTER
            ? config('pricing.Compel.sellerId')
            : config('pricing.CompelDms.sellerId');
        $compel = new CompelApiService();
        $ret = collect();
        $response = $this->store === self::CENTER ? $compel->searchInCenter($search) : $compel->searchByName($search);
        foreach ($response->result->items as $item) {
            // Log::info('proposals', [$item]);
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
            if ($sellerGood->isDirty()) $sellerGood->save();
            $sellerWarehouses = collect();
            $proposals = $this->store === self::CENTER ? $item->locations : $item->proposals;
            foreach ($proposals as $proposal) {
                /*
                 * Если Дмс то откидываем CENTER
                 */
                if ($this->store === self::DMS && !empty(trim($proposal->location_id))) continue;
                /*
                 *  Количество для ДМС может браться из последней строчки прайса
                 */
                $quantity = $proposal->vend_qty;
                if ($quantity == 0 && count($proposal->price_qty) > 0) {
                    $quantity = $proposal->price_qty[count($proposal->price_qty) - 1]->max_qty;
                }
                $code = empty(trim($proposal->location_id))
                    ? $proposal->prognosis_id . ';' . $proposal->vend_type . ';' . $proposal->vend_qty . ';'
                    . $proposal->cut_tape . ';' . $proposal->vend_note
                    : null;
                /*
                 *   Негодяи не дают код склада, и могут быть похожие предложения,
                 *   тоесть код совпаает, а массив цен разазцый.
                 *   Если не выплнить рповерку то следующий массив цен обнулит предыдущий
                 */
                if ($sellerWarehouses->contains($code)) continue;
                $sellerWarehouses->push($code);
                $sellerWarehouse = SellerWarehouse::query()
                    ->firstOrNew(['seller_good_id' => $sellerGood->id, 'code' => $code]);
                $cutTape = '';
                if (!empty($proposal->cut_tape) && $proposal->cut_tape) {
                    $cutTape = ', обрезки';
                }
                if (!empty($proposal->vend_type) && !empty($this->supplierTypes[$proposal->vend_type]))  {
                    $proposal->vend_type = $this->supplierTypes[$proposal->vend_type] . $cutTape;
                }
                $sellerWarehouse->fill([
                    'quantity' => $quantity,
                    'additional_delivery_time' => $proposal->prognosis_days - 1,
                    'multiplicity' => $proposal->mpq,
                    'options' => $proposal,
                    'remark' => '',
                ]);
                if ($sellerWarehouse->isDirty()) $sellerWarehouse->save();
                $sellerWarehouse->sellerGood = $sellerGood;
                // $sellerWarehouse->sellerPrices()->delete();
                if ($quantity > 0) {
                    $sellerPrices = collect();
                    foreach ($proposal->price_qty as $price) {
                        $sellerPrice = new SellerPrice();
                        $sellerPrice->fill([
                            'id' => Uuid::uuid4()->toString(),
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
                        $sellerPrices->push(clone $sellerPrice);
                        // $sellerPrice->save([ 'timestamps' =>  false ]);
                        $sellerPrice->sellerWarehouse = $sellerWarehouse;
                        $ret->push($sellerPrice);
                    }
                    ProcessUpdateSellerPrices::dispatch($sellerPrices, $sellerWarehouse, $explode);
                }
            }
        }
        return $ret;
    }
}
