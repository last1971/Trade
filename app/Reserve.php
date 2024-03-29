<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Reserve extends Model
{
    /**
     * @var bool
     */
    public static $snakeAttributes = false;

    /**
     * @var bool
     */
    public $timestamps = false;

    protected $connection = 'firebird';

    protected $primaryKey = 'RESERVCODE';

    protected $table = 'RESERVEDPOS';

    protected $fillable = ['QUANSKLAD'];

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
