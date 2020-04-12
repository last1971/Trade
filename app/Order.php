<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    //
    public $timestamps = false;

    protected $connection = 'firebird';

    protected $primaryKey = 'ID';

    protected $table = 'ZAKAZ_MASTER';

    public function orderLines()
    {
        return $this->hasMany('App\Order', 'MASTER_ID', 'ID');
    }
}
