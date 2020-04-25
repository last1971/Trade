<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Seller extends Model
{
    //
    public $timestamps = false;

    protected $connection = 'firebird';

    protected $primaryKey = 'WHEREISPOSTCODE';

    protected $table = 'WHEREISPOST';

    public function orders()
    {
        return $this->hasMany('App\Order', 'WHEREISPOSTCODE', 'WHEREISPOSTCODE');
    }
}
