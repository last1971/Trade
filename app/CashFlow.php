<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CashFlow extends Model
{
    //
    public $timestamps = false;

    protected $connection = 'firebird';

    protected $primaryKey = 'SCHETCODE';

    protected $table = 'SCHET';

    public function invoice()
    {
        return $this->belongsTo('App\Invoice', 'SCODE', 'SCODE');
    }
}
