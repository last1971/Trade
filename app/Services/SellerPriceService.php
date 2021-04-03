<?php


namespace App\Services;


use App\Entry;
use App\Good;
use App\SellerPrice;
use App\Warehouse;
use Carbon\Carbon;
use Error;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class SellerPriceService
{
    public array $aliases = [
        0    => ['function' => 'fromElco', 'trim' => true, 'ereg' => true,],
        857  => ['function' => 'fromCompel', 'trim' => true, 'ereg' => false,],
        1068 => ['function' => 'fromDan', 'trim' => true, 'ereg' => true,],
    ];

    public array $cacheTimes = [
        0    => 60,
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
                'sellerId' => 0,
                'packageQuantity' => 1,
                'multiplicity' => 1,
                'quantity' => $good->quantity,
                'minQuantity' => 1,
                'maxQuantity' => 0,
                'price' => $good->price,
                'CharCode' => 'RUB',
                'isInput' => true,
                'delivery_time' => 0,
                'isApi' => true,
                'updatedAt' => Carbon::now(),
            ];
            $ret->push($line);
            if ($good->retailPrice && !empty($good->retailPrice->PRICEROZN)) {
                $lineRozn = $line;
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
            'multiplicity' => $price->sellerWarehouse->muliplicity,
            'quantity' => $price->sellerWarehouse->quantity,
            'minQuantity' => $price->min_quantity,
            'maxQuantity' => $price->max_quantity,
            'price' => $price->value,
            'CharCode' => $price->CharCode,
            'isInput' => $price->is_input,
            'delivery_time' => $price->sellerWarehouse->sellerGood->basic_delivery_time
                + $price->sellerWarehouse->additional_delivery_time,
            'isApi' => false,
            'updatedAt' => $price->updated_at,
        ])->toArray();
    }

    private function fromCompel(string $search, bool $file = true): array
    {
        if ($file) {
            return $this->fromDataBase($search, 857);
        }
        return [];
    }

    private function fromDan(string $search): array
    {
        return $this->fromDataBase($search, 1068);
    }
}
