<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InvoiceLine extends Model
{
    //
    public $timestamps = false;

    protected $connection = 'firebird';

    protected $primaryKey = 'REALPRICECODE';

    protected $table = 'REALPRICE';

    public function invoice()
    {
        return $this->belongsTo('App\Invoice', 'SCODE', 'SCODE');
    }

    public function transferOutLines()
    {
        return $this->hasMany('App\TransferOutLine', 'REALPRICECODE', 'REALPRICECODE');
    }
}
