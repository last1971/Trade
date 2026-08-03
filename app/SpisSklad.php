<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SpisSklad extends Model
{
    //
    public $timestamps = false;

    protected $connection = 'firebird';

    protected $primaryKey = 'SPISSKLADCODE';

    protected $table = 'SPISSKLAD';

    public function good()
    {
        return $this->belongsTo('App\Good', 'GOODSCODE', 'GOODSCODE');
    }

    public function name()
    {
        return $this->hasOneThrough(
            'App\Name',
            'App\Good',
            'GOODSCODE',
            'NAMECODE',
            'GOODSCODE',
            'NAMECODE'
        );
    }

    public function reason()
    {
        return $this->belongsTo('App\SpisReason', 'OSN_SPIS_ID', 'ID');
    }
}
