<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OrderLine extends Model
{
    //
    public $timestamps = false;

    protected $connection = 'firebird';

    protected $primaryKey = 'ID';

    protected $table = 'ZAKAZ_DETAIL';

    public function good()
    {
        return $this->belongsTo('App\Good', 'GOODSCODE', 'GOODSCODE');
    }

    public function order()
    {
        return $this->belongsTo('App\Order', 'MASTER_ID', 'ID');
    }

    public function storeLines()
    {
        return $this->hasMany('App\OrderLine', 'ZAKAZ_DETAIL_ID', 'ID');
    }

}
