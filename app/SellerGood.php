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
        'updated_at'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

//    protected $dispatchesEvents = [
//        'updated' => SellerGoodUpdated::class,
//    ];

    protected static function booted()
    {
        static::saved(function (SellerGood $sellerGood) {
            if ($sellerGood->wasChanged('good_id')) $sellerGood->clearSearchingCache();
        });
    }

    public function clearSearchingCache(): bool
    {
        $keyGoodId = 'sellerGoodId=' . $this->id;
        $result = Cache::has($keyGoodId);
        if ($result) {
            $keys = Cache::get($keyGoodId);
            $keys->each(function ($key) {
                Cache::forget($key);
            });
            Cache::forget('sellerGoodId=' . $keyGoodId);
        }
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
