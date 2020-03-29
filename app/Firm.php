<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Firm extends Model
{
    //
    public $timestamps = false;
    protected $connection = 'firebird';
    protected $table = 'FIRMS';

    public function invoices()
    {
        return $this->hasMany('App\Invoice', 'FIRM_ID', 'FIRM_ID');
    }

}
