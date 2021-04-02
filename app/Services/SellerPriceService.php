<?php


namespace App\Services;


use App\Entry;
use App\Good;
use App\Warehouse;
use Carbon\Carbon;
use Error;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class SellerPriceService
{
    public array $aliases = [
        0    => 'fromElco',
        857  => 'fromCompel',
        1068 => 'fromDan',
    ];

    public array $cacheTimes = [
        0    => 60,
        857  => 3600,
        1068 => 86400,
    ];

    private function isSeller(int $id): bool
    {
        return array_key_exists($id, $this->aliases);
    }

    public function get(string $search, int $sellerId, bool $file = true, bool $update = false): array
    {
        throw_if(!$this->isSeller($sellerId), new Error('Bad Seller!'));
        throw_if(mb_strlen($search) < 3, new Error('Search string is short!'));
        $key = 'sellerId=' . $sellerId . ' ' . $search;
        $result = array();
        if (!$update && Cache::has($key)) {
            $result['data'] = Cache::get($key);
            $result['cache'] = true;
        } else {
            $result['data'] = $this->{$this->aliases[$sellerId]}($search, $file);
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
                'sellerId' => 0,
                'package_quantity' => 1,
                'multiplicity' => 1,
                'quantity' => $good->quantity,
                'min_quantity' => 1,
                'max_quantity' => 0,
                'price' => $good->price,
                'CharCode' => 'RUB',
                'is_input' => true,
                'delivery_time' => 0,
                'is_api' => true,
                'updated_at' => Carbon::now(),
            ];
            $ret->push($line);
            if ($good->retailPrice && !empty($good->retailPrice->PRICEROZN)) {
                $lineRozn = $line;
                $lineRozn['is_input'] = false;
                $lineRozn['price'] = $good->retailPrice->PRICEROZN;
                if (!empty($good->retailPrice->QUANMOPT) && $good->retailPrice->QUANMOPT > 1) {
                    $lineRozn['max_quantity'] = $good->retailPrice->QUANMOPT - 1;
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
                $lineMopt['is_input'] = false;
                $lineMopt['price'] = $good->retailPrice->PRICEMOPT;
                $lineMopt['min_quantity'] = $good->retailPrice->QUANMOPT;
                if (
                    !empty($good->retailPrice->QUANOPT)
                    &&
                    $good->retailPrice->QUANOPT > $good->retailPrice->QUANMOPT
                ) {
                    $lineMopt['max_quantity'] = $good->retailPrice->QUANOPT - 1;
                } else {
                    $lineMopt['max_quantity'] = $good->retailPrice->QUANMOPT;
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
                $lineOpt['is_input'] = false;
                $lineOpt['price'] = $good->retailPrice->PRICEOPT;
                $lineOpt['min_quantity'] = $good->retailPrice->QUANOPT;
                $ret->push($lineOpt);
            }
        }
        return $ret->toArray();
    }

    private function fromCompel(string $search, bool $file = true): array
    {
        return [];
    }

    private function fromDan(string $search): array
    {
        return [];
    }
}
