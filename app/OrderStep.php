<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OrderStep extends Model
{
    //
    public $timestamps = false;

    protected $connection = 'firebird';

    protected $primaryKey = 'ID';

    protected $table = 'BOUND_QUAN';

    public function good()
    {
        return $this->belongsTo('App\Good', 'GOODSCODE', 'GOODSCODE');
    }

}
