<?php


namespace App\Services;


use App\Entry;
use App\Good;
use App\SellerGood;
use App\SellerPrice;
use App\SellerWarehouse;
use App\Warehouse;
use Carbon\Carbon;
use Error;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class SellerPriceService
{
    public array $aliases = [
        0    => ['function' => 'fromElco', 'trim' => true, 'ereg' => true, 'basic_delivery_time' => 0],
        857  => ['function' => 'fromCompel', 'trim' => true, 'ereg' => false, 'basic_delivery_time' => 7],
        1068 => ['function' => 'fromDan', 'trim' => true, 'ereg' => true, 'basic_delivery_time' => 3],
    ];

    public array $cacheTimes = [
        0    => 120,
        857  => 3600,
        1068 => 86400,
    ];

    /**
     * @param int $id
     * @return bool
     */
    private function isSeller(int $id): bool
    {
        return array_key_exists($id, $this->aliases);
    }

    /**
     * @param string $function
     * @return int
     * @throws \Throwable
     */
    private function sellerCode(string $function): int
    {
        $ret = -1;
        foreach ($this->aliases as $code => $alias) {
            if ($alias['function'] === $function) {
                $ret = $code;
                break;
            }
        }
        throw_if($ret < 0, new Error('Wrong function name'));
        return $code;
    }

    /**
     * @param string $search
     * @param int $sellerId
     * @param bool $file
     * @param bool $update
     * @return array
     * @throws \Throwable
     */
    public function get(string $search, int $sellerId, bool $file = true, bool $update = false): array
    {
        $processedSearch = $search;
        if ($this->aliases[$sellerId]['trim']) {
            $processedSearch = trim($processedSearch);
        }
        if ($this->aliases[$sellerId]['ereg']) {
            $processedSearch = mb_ereg_replace(
                config('app.search_replace'), '', $processedSearch
            );
        }
        throw_if(!$this->isSeller($sellerId), new Error('Bad Seller!'));
        throw_if(mb_strlen($processedSearch) < 3, new Error('Search string is short!'));
        $key = 'sellerId=' . $sellerId . ';search=' . $processedSearch . ';file=' . $file;
        $result = array();
        if (!$update && Cache::has($key)) {
            $result['data'] = Cache::get($key);
            $result['cache'] = true;
        } else {
            $result['data'] = $this->{$this->aliases[$sellerId]['function']}($processedSearch, $file);
            Cache::put($key, $result['data'], $this->cacheTimes[$sellerId]);
            $result['cache'] = false;
        }
        return $result;
    }

    private function fromElco(string $search): array
    {
        $sellerId = $this->sellerCode(__FUNCTION__);
        $goods = Good::with('name', 'retailPrice')
            ->where('HIDDEN', 0)
            ->whereHas('goodNames', function (Builder $query) use ($search) {
                return $query->where('NAME', 'CONTAINING', $search);
            })
            ->whereHas('warehouse', function (Builder $query) {
                return $query->where('QUAN', '>', 0);
            })
            ->addSelect([
                'quantity' => Warehouse::query()
                    ->whereColumn('GOODSCODE', 'GOODS.GOODSCODE')
                    ->selectRaw('coalesce(sum(QUAN), 0)'),
                'price' => Entry::query()
                    ->whereColumn('GOODSCODE', 'GOODS.GOODSCODE')
                    ->whereNotNull('SKLADINCODE')
                    ->select('PRICE')
                    ->orderByDesc('DATA')
                    ->take(1)
            ])
            ->take(100)
            ->get();
        $ret = collect();
        foreach ($goods as $good) {
            $line = [
                'name' => $good->name->NAME,
                'producer' => $good->PRODUCER,
                'case' => $good->BODY,
                'remark' => $good->DESCRIPTION,
                'id' => Str::uuid(),
                'code' => $good->GOODSCODE,
                'goodId' => $good->GOODSCODE,
                'sellerId' => $sellerId,
                'packageQuantity' => 1,
                'multiplicity' => 1,
                'quantity' => $good->quantity,
                'minQuantity' => 1,
                'maxQuantity' => 0,
                'price' => $good->price,
                'CharCode' => 'RUB',
                'isInput' => true,
                'deliveryTime' => $this->aliases[$sellerId]['basic_delivery_time'],
                'isSomeoneElsesWarehouse' => false,
                'isApi' => true,
                'options' => null,
                'updatedAt' => Carbon::now(),
            ];
            $ret->push($line);
            if ($good->retailPrice && !empty($good->retailPrice->PRICEROZN)) {
                $lineRozn = $line;
                $lineRozn['id'] = Str::uuid();
                $lineRozn['isInput'] = false;
                $lineRozn['price'] = $good->retailPrice->PRICEROZN;
                if (!empty($good->retailPrice->QUANMOPT) && $good->retailPrice->QUANMOPT > 1) {
                    $lineRozn['maxQuantity'] = $good->retailPrice->QUANMOPT - 1;
                }
                $ret->push($lineRozn);
            }
            if ($good->retailPrice
                &&
                !empty($good->retailPrice->PRICEMOPT)
                &&
                !empty($good->retailPrice->QUANMOPT)
            ) {
                $lineMopt = $line;
                $lineMopt['id'] = Str::uuid();
                $lineMopt['isInput'] = false;
                $lineMopt['price'] = $good->retailPrice->PRICEMOPT;
                $lineMopt['minQuantity'] = $good->retailPrice->QUANMOPT;
                if (
                    !empty($good->retailPrice->QUANOPT)
                    &&
                    $good->retailPrice->QUANOPT > $good->retailPrice->QUANMOPT
                ) {
                    $lineMopt['maxQuantity'] = $good->retailPrice->QUANOPT - 1;
                } else {
                    $lineMopt['maxQuantity'] = $good->retailPrice->QUANMOPT;
                }
                $ret->push($lineMopt);
            }
            if ($good->retailPrice
                &&
                !empty($good->retailPrice->PRICEOPT)
                &&
                !empty($good->retailPrice->QUANOPT)
            ) {
                $lineOpt = $line;
                $lineOpt['id'] = Str::uuid();
                $lineOpt['isInput'] = false;
                $lineOpt['price'] = $good->retailPrice->PRICEOPT;
                $lineOpt['minQuantity'] = $good->retailPrice->QUANOPT;
                $ret->push($lineOpt);
            }
        }
        return $ret->toArray();
    }

    private function fromDataBase(string $search, int $sellerId): array
    {
        $prices = SellerPrice::with('sellerWarehouse.sellerGood')
            ->whereHas('sellerWarehouse.sellerGood', function (Builder $query) use ($search, $sellerId) {
                return $query->where('is_active', true)
                    ->where('seller_id', $sellerId)
                    ->where('search_name', 'like', '%' . $search . '%');
            })
            ->take(100)
            ->get();
        return $prices->map(fn($price) => [
            'name' => $price->sellerWarehouse->sellerGood->name,
            'producer' => $price->sellerWarehouse->sellerGood->producer,
            'case' => $price->sellerWarehouse->sellerGood->case,
            'remark' => $price->sellerWarehouse->sellerGood->remark . ' ' . $price->sellerWarehouse->remark,
            'id' => $price->id,
            'code' => $price->sellerWarehouse->sellerGood->code,
            'goodId' => $price->sellerWarehouse->sellerGood->good_id,
            'sellerId' => $price->sellerWarehouse->sellerGood->seller_id,
            'packageQuantity' => $price->sellerWarehouse->sellerGood->package_quantity,
            'multiplicity' => $price->sellerWarehouse->multiplicity,
            'quantity' => $price->sellerWarehouse->quantity,
            'minQuantity' => $price->min_quantity,
            'maxQuantity' => $price->max_quantity,
            'price' => $price->value,
            'CharCode' => $price->CharCode,
            'isInput' => $price->is_input,
            'deliveryTime' => $price->sellerWarehouse->sellerGood->basic_delivery_time
                + $price->sellerWarehouse->additional_delivery_time,
            'isSomeoneElsesWarehouse' => $price->sellerWarehouse->additional_delivery_time > 0,
            'isApi' => false,
            'options' => null,
            'updatedAt' => $price->updated_at,
        ])->toArray();
    }

    private function fromCompel(string $search, bool $file = true): array
    {
        $sellerId = $this->sellerCode(__FUNCTION__);
        if ($file) {
            return $this->fromDataBase($search, $sellerId);
        }
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
                'basic_delivery_time' => $this->aliases[$sellerId]['basic_delivery_time'],
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
                        $ret->push([
                            'name' => $item->item_name,
                            'producer' => $item->item_brend,
                            'case' => $item->package_name,
                            'remark' => $sellerWarehouse->remark,
                            'id' => $sellerPrice->id,
                            'code' => $item->item_id,
                            'goodId' => $sellerGood->good_id,
                            'sellerId' => $sellerGood->seller_id,
                            'packageQuantity' => $sellerGood->package_quantity,
                            'multiplicity' => $sellerWarehouse->multiplicity,
                            'quantity' => $quantity,
                            'minQuantity' => $sellerPrice->min_quantity,
                            'maxQuantity' => $sellerPrice->max_quantity,
                            'price' => $sellerPrice->value,
                            'CharCode' => $sellerPrice->CharCode,
                            'isInput' => $sellerPrice->is_input,
                            'deliveryTime' => $sellerGood->basic_delivery_time
                                + $sellerWarehouse->additional_delivery_time,
                            'isSomeoneElsesWarehouse' => $sellerWarehouse->additional_delivery_time > 0,
                            'isApi' => true,
                            'options' => $sellerWarehouse->options,
                            'updatedAt' => $sellerPrice->updated_at,
                        ]);
                    }
                }
            }
        }
        return $ret->toArray();
    }

    private function fromDan(string $search): array
    {
        $sellerId = $this->sellerCode(__FUNCTION__);
        return $this->fromDataBase($search, $sellerId);
    }
}
