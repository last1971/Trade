<?php

namespace App;

use App\ModelTraits\InnKppTrait;
use Illuminate\Database\Eloquent\Model;

class Buyer extends Model
{
    use InnKppTrait;

    public $timestamps = false;

    protected $connection = 'firebird';

    protected $primaryKey = 'POKUPATCODE';

    protected $table = 'POKUPAT';

    public function advancedBuyer()
    {
        return $this->hasOne('App\AdvancedBuyer', 'buyer_id', 'POKUPATCODE')
            ->withDefault();
    }

    public function invoices()
    {
        return $this->hasMany('App\Invoice', 'POKUPATCODE', 'POKUPATCODE');
    }

    public function retailOrders()
    {
        return $this->hasMany('App\RetailOrder', 'POKUPATCODE', 'POKUPATCODE');
    }

    public function transferOuts()
    {
        return $this->hasMany('App\TransferOut', 'POKUPATCODE', 'POKUPATCODE');
    }
}
