<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Buyer extends Model
{
    //
    public $timestamps = false;

    protected $connection = 'firebird';

    protected $primaryKey = 'POKUPATCODE';

    protected $table = 'POKUPAT';

    public function advancedBuyer()
    {
        return $this->hasOne('App\AdvancedBuyer', 'POCUPATCODE', 'buyer_id');
    }

    public function invoices()
    {
        return $this->hasMany('App\Invoice', 'POKUPATCODE', 'POKUPATCODE');
    }

    public function transferOuts()
    {
        return $this->hasMany('App\TransferOut', 'POKUPATCODE', 'POKUPATCODE');
    }
}
