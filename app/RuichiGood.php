<?php

namespace App;

use App\Jobs\ProcessUpdateSellerPrices;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Uuid;

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

    public function sellerPrices(array $exclude = []): array
    {
        $sellerGood = SellerGood::query()->firstOrNew([
            'seller_id' => config('pricing.Ruichi.sellerId'),
            'code' => $this->id,
        ]);
        $sellerGood->fill([
            'name' => $this->tovmark,
            'producer' => $this->ruichiWharehouses->first()->brand ?? null,
            'basic_delivery_time' => config('pricing.Ruichi.basicDeliveryTime'),
            'remark' => $this->ruichiWharehouses
                ->unique('notes')
                ->implode('notes', ', '),
            'packageQuantity' => $this->ruichiWharehouses->min('nu') ?? 1,
            'is_active' => $this->fost > 0
        ]);
        if ($sellerGood->isDirty()) $sellerGood->save();

        if (!$sellerGood->is_active) return [];

        $sellerWarehouse = SellerWarehouse::query()->firstOrNew(['seller_good_id' => $sellerGood->id]);
        $sellerWarehouse->fill([
            'quantity' => $this->fost,
            'additional_delivery_time' => 0,
            'multiplicity' => $this->ruichiWharehouses->min('no_ot') ?? 1,
            'remark' => '',
            'options' => null,
        ]);
        if ($sellerWarehouse->isDirty()) $sellerWarehouse->save();
        $sellerWarehouse->sellerGood = $sellerGood;

        $ret = [];

        //$sellerWarehouse->sellerPrices()->delete();
        $sellerPrices = collect();

        $sellerPrice = new SellerPrice([
            'id' => Uuid::uuid4()->toString(),
            'seller_warehouse_id' =>  $sellerWarehouse->id,
            'min_quantity' => (int) $sellerWarehouse->multiplicity,
            'max_quantity' => 0,
            'value' => $this->price_2,
            'CharCode' => 'RUB',
            'is_input' => true,
            'updated_at' => Carbon::now(),
        ]);
        // $sellerWarehouse->sellerPrices()->save($sellerPrice);
        $sellerPrices->push(clone $sellerPrice);
        $sellerPrice->sellerWarehouse = $sellerWarehouse;
        array_push($ret, $sellerPrice);

        foreach ($this->ruichiPrices as $index => $ruichiPrice) {
            $maxQuantity = $this->ruichiPrices->count() > $index + 1
                ? intval($this->ruichiPrices->get($index + 1)->quant) - 1
                : 0;
            $sellerPrice = new SellerPrice([
                'id' => Uuid::uuid4()->toString(),
                'seller_warehouse_id' =>  $sellerWarehouse->id,
                'min_quantity' => intval($ruichiPrice->quant),
                'max_quantity' => $maxQuantity,
                'value' => $ruichiPrice->sale_price,
                'CharCode' => 'RUB',
                'is_input' => false,
                'updated_at' => Carbon::now(),
            ]);
            // $sellerWarehouse->sellerPrices()->save($sellerPrice);
            $sellerPrices->push(clone $sellerPrice);
            $sellerPrice->sellerWarehouse = $sellerWarehouse;
            array_push($ret, $sellerPrice);
        }
        $sortedPrices = collect($sellerPrices->sortBy([['min_quantity', 'asc'], ['is_input', 'desc']])->all());
        ProcessUpdateSellerPrices::dispatch($sortedPrices, $sellerWarehouse, $exclude);
        return $ret;
    }
}
