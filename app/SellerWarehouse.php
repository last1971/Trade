<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SellerWarehouse extends Model
{
    use HasFactory;

    protected $connection = 'mysql';

    public function sellerGood()
    {
        return $this->belongsTo('App\SellerGood');
    }
}
