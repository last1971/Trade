<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RetailOrderLine extends Model
{
    //
    public $timestamps = false;

    protected $connection = 'firebird';

    protected $primaryKey = 'ID';

    protected $table = 'ROZN_DETAIL';

    public function retailOrder()
    {
        return $this->belongsTo('App\ReatilOrder', 'MASTER_ID', 'ID');
    }

    public function good()
    {
        return $this->belongsTo('App\Good', 'GOODSCODE', 'GOODSCODE');
    }
}
