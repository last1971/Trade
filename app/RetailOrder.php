<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RetailOrder extends Model
{
    //
    public $timestamps = false;

    protected $connection = 'firebird';

    protected $primaryKey = 'ID';

    protected $table = 'ROZN_MASTER';

    public function retailOrderLines()
    {
        return $this->hasMany('App\ReatilOrderLine', 'MASTER_ID', 'ID');
    }
}
