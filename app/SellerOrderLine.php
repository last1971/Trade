<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SellerOrderLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_order_id',
        'item_id',
        'item_name',
        'qty',
        'price',
        'price_data',
    ];

    protected $casts = [
        'price_data' => 'array', // автоматически json <-> array
        'qty' => 'integer',
        'price' => 'float',
    ];

    public function sellerOrder()
    {
        return $this->belongsTo(SellerOrder::class);
    }
}
