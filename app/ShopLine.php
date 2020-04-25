<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ShopLine extends Model
{
    //
    public $timestamps = false;

    protected $connection = 'firebird';

    protected $primaryKey = 'SHOPINCODE';

    protected $table = 'SHOPIN';

    public function good()
    {
        return $this->belongsTo('App\Good', 'GOODSCODE', 'GOODSCODE');
    }

    public function orderLine()
    {
        return $this->belongsTo('App\OrderLine', 'ZAKAZ_DETAIL_ID', 'ID');
    }

    public function entry()
    {
        return $this->hasOne('App\Entry', 'SHOPINCODE', 'SHOPINCODE');
    }
}
