<?php

namespace App;

use App\Events\SellerGoodUpdated;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SellerGood extends Model
{
    use HasFactory;

    protected $connection = 'mysql';

    protected $fillable = [
        'name',
        'producer',
        'case',
        'code',
        'seller_id',
        'good_id',
        'is_active',
        'basic_delivery_time',
        'remark',
        'package_quantity',
        'updated_at',
        'pos',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'pos' => 'boolean',
    ];

//    protected $dispatchesEvents = [
//        'updated' => SellerGoodUpdated::class,
//    ];

    protected static function booted()
    {
        static::saved(function (SellerGood $sellerGood) {
            if ($sellerGood->wasChanged('good_id')) {
                // Log::info('Change GoodId', ['GoodId' => $sellerGood->good_id]);
                $sellerGood->clearSearchingCache();
            }
        });
    }

    public function clearSearchingCache(array $exclude = []): bool
    {
        // Log::info('Clear', ['SellerGoodId' => $this->id, 'exclude' => $exclude ]);
        $keyGoodId = 'sellerGoodId=' . $this->id;
        $result = Cache::has($keyGoodId);
        if ($result) {
            $keys = Cache::get($keyGoodId);
            $keys->each(function ($key) use ($exclude) {
                if (!in_array($key, $exclude)) Cache::forget($key);
            });
            if (empty($exclude)) Cache::forget('sellerGoodId=' . $keyGoodId);
        }
        /*
        Log::info(
            'State Cache',
            [
                $exclude[0] => Cache::has($exclude[0]),
                $exclude[1] => Cache::has($exclude[1]),
                'sellerGoodId' => Cache::has($keyGoodId),
            ]
        );
        */
        return $result;
     }

    public function setNameAttribute(string $value)
    {
        $this->attributes['name'] = $value;
        $this->attributes['search_name'] = mb_ereg_replace(
            config('app.search_replace'), '', $value
        );
    }

    public function seller()
    {
        return $this->belongsTo('App\Seller', 'WHEREISPOSTCODE', 'seller_id');
    }

    public function good()
    {
        return $this->belongsTo('App\Good', 'GOODSCODE', 'good_id');
    }

    public function sellerWarehouses()
    {
        return $this->hasMany('App\SellerWarehouse');
    }

}
