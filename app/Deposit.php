<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Deposit extends Model
{
    public static $snakeAttributes = false;

    public $timestamps = false;

    protected $connection = 'firebird';

    protected $primaryKey = 'DEPOSITCODE';

    protected $table = 'DEPOSIT';

    public function invoice()
    {
        return $this->belongsTo('App\Invoice', 'SCODE', 'SCODE');
    }

    public function buyer()
    {
        return $this->belongsTo('App\Buyer', 'POKUPATCODE', 'POKUPATCODE');
    }
}
