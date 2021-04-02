<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SellerWarehouse extends Model
{
    use HasFactory;

    protected $connection = 'mysql';

    protected $fillable = ['seller_good_id', 'quantity', 'additional_delivery_time', 'multiplicity', 'remark'];

    public function sellerGood()
    {
        return $this->belongsTo('App\SellerGood');
    }
}
