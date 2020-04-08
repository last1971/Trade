<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TransferOut extends Model
{
    //
    public $timestamps = false;

    protected $connection = 'firebird';

    protected $primaryKey = 'SFCODE';

    protected $table = 'SF';

    public function transferOutLines()
    {
        return $this->hasMany('App\TransferOutLine', 'SFCODE', 'SFCODE');
    }
}
