<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InvoiceLine extends Model
{
    //
    public $timestamps = false;
    protected $connection = 'firebird';
    protected $table = 'REALPRICE';

    public function invoice()
    {
        return $this->belongsTo('App\Invoice', 'SCODE', 'SCODE');
    }
}
