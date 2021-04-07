<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SellerWarehouse extends Model
{
    use HasFactory;

    protected $connection = 'mysql';

    protected $fillable = [
        'seller_good_id',
        'code',
        'quantity',
        'additional_delivery_time',
        'multiplicity',
        'remark'
    ];

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
