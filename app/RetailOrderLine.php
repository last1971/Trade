<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RetailOrderLine extends Model
{
    public static $snakeAttributes = false;

    public $timestamps = false;

    protected $connection = 'firebird';

    protected $primaryKey = 'ID';

    protected $table = 'ROZN_DETAIL';

    public function retailOrder()
    {
        return $this->belongsTo('App\RetailOrder', 'MASTER_ID', 'ID');
    }

    public function good()
    {
        return $this->belongsTo('App\Good', 'GOODSCODE', 'GOODSCODE');
    }

    public function reserves()
    {
        return $this->hasMany('App\RetailOrderLine', 'ROZN_DETAIL_ID', 'ID');
    }
}
