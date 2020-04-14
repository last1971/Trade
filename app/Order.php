<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    //
    public $timestamps = false;

    protected $connection = 'firebird';

    protected $fillable = ['WHEREISPOSTCODE', 'INVOICE_NUM', 'INVOICE_DATA', 'DATA_PRIH', 'PRIM', 'STATUS'];

    protected $primaryKey = 'ID';

    protected $table = 'ZAKAZ_MASTER';

    public function cashFlows()
    {
        return $this->hasMany('App\CashFlow', 'ZAKAZ_MASTER_ID', 'ID');
    }

    public function employee()
    {
        return $this->belongsTo('App\Employee', 'STAFF_ID', 'ID');
    }

    public function orderLines()
    {
        return $this->hasMany('App\OrderLine', 'MASTER_ID', 'ID');
    }

    public function seller()
    {
        return $this->belongsTo('App\Seller', 'WHEREISPOSTCODE', 'WHEREISPOSTCODE');
    }

}
