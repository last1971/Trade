<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\ModelTraits\UuidTrait;

class SellerPrice extends Model
{
    use HasFactory, UuidTrait;

    protected $connection = 'mysql';

    protected $fillable = ['seller_warehouse_id', 'min_quantity', 'max_quantity', 'value', 'CharCode', 'is_input'];

    public function sellerWarehouse()
    {
        return $this->belongsTo('App\SellerWarehouse');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'CharCode', 'CharCode');
    }
}
