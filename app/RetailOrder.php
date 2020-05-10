<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RetailOrder extends Model
{
    public static $snakeAttributes = false;

    public $timestamps = false;

    protected $connection = 'firebird';

    protected $primaryKey = 'ID';

    protected $table = 'ROZN_MASTER';

    public function retailOrderLines()
    {
        return $this->hasMany('App\ReatilOrderLine', 'MASTER_ID', 'ID');
    }

    public function buyer()
    {
        return $this->belongsTo('App\Buyer', 'POKUPATCODE', 'POKUPATCODE');
    }
}
