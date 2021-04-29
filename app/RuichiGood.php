<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RuichiGood extends Model
{
    use HasFactory;

    protected $connection = 'ruichi';

    protected $table = 'mainzip';

    protected $with = ['ruichiPrices', 'ruichiWharehouses'];

    public function ruichiPrices()
    {
        return $this->hasMany(RuichiPrice::class, 'mainbase')->orderBy('quant');
    }

    public function ruichiWharehouses()
    {
        return $this->hasMany(RuichiWharehouse::class, 'tovcode')
            ->where('nu', '>', 0)
            ->where('no_ot', '>', 0);
    }

    public function sellerPrices(): array
    {
        $sellerGood = SellerGood::query()->firstOrNew([
            'seller_id' => config('pricing.Ruichi.sellerId'),
            'code' => $this->id,
        ]);
        $sellerGood->fill([
            'name' => $this->tovmark,
            'producer' => $this->ruichiWharehouses->first()->brand,
            'basic_delivery_time' => config('pricing.Ruichi.basicDeliveryTime'),
            'remark' => $this->ruichiWharehouses
                ->unique('notes')
                ->implode('notes', ', '),
            'packageQuantity' => $this->ruichiWharehouses->min('nu') ?? 1,
            'is_active' => $this->fost > 0
        ]);
        $sellerGood->save();

        if (!$sellerGood->is_active) return [];

        $sellerWarehouse = SellerWarehouse::query()->firstOrNew(['seller_good_id' => $sellerGood->id]);
        $sellerWarehouse->fill([
            'quantity' => $this->fost,
            'additional_delivery_time' => 0,
            'multiplicity' => $this->ruichiWharehouses->min('no_ot') ?? 1,
            'remark' => '',
            'options' => null,
        ]);
        $sellerWarehouse->save();
        $sellerWarehouse->sellerGood = $sellerGood;

        $ret = [];

        $sellerWarehouse->sellerPrices()->delete();
        $sellerPrice = new SellerPrice([
            'min_quantity' => $sellerWarehouse->multiplicity,
            'max_quantity' => 0,
            'value' => $this->price_2,
            'CharCode' => 'RUB',
            'is_input' => true,
        ]);
        $sellerWarehouse->sellerPrices()->save($sellerPrice);
        $sellerPrice->sellerWarehouse = $sellerWarehouse;
        array_push($ret, $sellerPrice);

        foreach ($this->ruichiPrices as $index => $ruichiPrice) {
            $maxQuantity = $this->ruichiPrices->count() > $index + 1
                ? $this->ruichiPrices->get($index + 1)->quant - 1
                : 0;
            $sellerPrice = new SellerPrice([
                'min_quantity' => $ruichiPrice->quant,
                'max_quantity' => $maxQuantity,
                'value' => $ruichiPrice->sale_price,
                'CharCode' => 'RUB',
                'is_input' => false,
            ]);
            $sellerWarehouse->sellerPrices()->save($sellerPrice);
            $sellerPrice->sellerWarehouse = $sellerWarehouse;
            array_push($ret, $sellerPrice);
        }
        return $ret;
    }
}
