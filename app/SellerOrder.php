<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SellerOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_id',
        'date',
        'number',
        'remark',
        'closed',
    ];

    protected $casts = [
        'date' => 'date',
        'closed' => 'boolean',
    ];

    protected $appends = ['amount'];

    public function getNumberAttribute($value)
    {
        return $value ?? $this->id;
    }

    public function getAmountAttribute()
    {
        return $this->lines()->sum(\DB::raw('qty * price'));
    }

    public function lines()
    {
        return $this->hasMany(SellerOrderLine::class);
    }

    public function seller()
    {
        return $this->belongsTo(Seller::class, 'seller_id', 'sellerId');
    }
}
