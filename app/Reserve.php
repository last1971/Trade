<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Reserve extends Model
{
    public static $snakeAttributes = false;

    public $timestamps = false;

    protected $connection = 'firebird';

    protected $primaryKey = 'RESERVCODE';

    protected $table = 'RESERVEDPOS';

    public function good()
    {
        return $this->belongsTo('App\Good', 'GOODSCODE', 'GOODSCODE');
    }

    public function invoice()
    {
        return $this->belongsTo('App\Invoice', 'SCODE', 'SCODE');
    }

    public function invoiceLine()
    {
        return $this->belongsTo('App\InvoiceLine', 'REALPRICECODE', 'REALPRICECODE');
    }

    public function retailOrderLine()
    {
        return $this->belongsTo('App\RetailOrderLine', 'ROZN_DETAIL_ID', 'ID');
    }
}
