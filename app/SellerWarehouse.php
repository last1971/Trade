<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;

class SellerWarehouse extends Model
{
    use HasFactory;

    protected $connection = 'mysql';

    protected $casts = [
        'options' => 'json',
    ];

    protected $fillable = [
        'seller_good_id',
        'code',
        'quantity',
        'additional_delivery_time',
        'multiplicity',
        'remark',
        'options',
        'sellerGood'
    ];

    protected static function booted()
    {
        static::saved(function (SellerWarehouse $sellerWarehouse) {
            if ($sellerWarehouse->wasChanged('quantity')) {
                // Log::info('Quantity change', ['quantity' => $sellerWarehouse->quantity]);
                $sellerWarehouse->sellerGood->clearSearchingCache();
            }
        });
    }

    /**
     * @return BelongsTo
     */
    public function sellerGood(): BelongsTo
    {
        return $this->belongsTo('App\SellerGood');
    }

    /**
     * @return HasMany
     */
    public function sellerPrices(): HasMany
    {
        return $this->hasMany('App\SellerPrice');
    }
}
