<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RetailPrice extends Model
{
    //
    public $timestamps = false;

    protected $connection = 'firebird';

    protected $primaryKey = 'PRICECODE';

    protected $table = 'PRICE';

    public function good()
    {
        return $this->belongsTo('App\Good', 'GOODSCODE', 'GOODSCODE');
    }
}
