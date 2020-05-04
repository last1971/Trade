<?php

namespace App;

use App\ModelTraits\InsertTrait;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use InsertTrait;

    public $timestamps = false;

    protected $connection = 'firebird';

    protected $fillable = ['WHEREISPOSTCODE', 'INVOICE_NUM', 'PRIM', 'STATUS', 'STAFF_ID'];

    protected $primaryKey = 'ID';

    protected $sequenceName = 'GEN_ZAKAZ_MASTER_ID';

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
