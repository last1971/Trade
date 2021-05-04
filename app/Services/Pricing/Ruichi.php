<?php


namespace App\Services\Pricing;


use App\RuichiGood;
use Illuminate\Support\Collection;

class Ruichi
{
    public function __invoke(string $search): Collection
    {
        $ruichiGoods = RuichiGood::query()
            ->where('tovmark1', 'like', '%' . $search . '%')
            ->where('fost', '>', 0)
            ->take(100)
            ->get();
        $ret = collect();
        foreach ($ruichiGoods as $ruichiGood) {
            $ret = $ret->merge($ruichiGood->sellerPrices());
        }
        return $ret;
    }
}
