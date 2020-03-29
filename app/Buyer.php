<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Buyer extends Model
{
    //
    public $timestamps = false;
    protected $connection = 'firebird';
    protected $table = 'POKUPAT';

    public function invoices()
    {
        return $this->hasMany('App\Invoice', 'POKUPATCODE', 'POKUPATCODE');
    }
}
