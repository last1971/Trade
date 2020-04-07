<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InvoiceLine extends Model
{
    //
    public $timestamps = false;

    protected $connection = 'firebird';

    protected $primaryKey = 'REALPRICECODE';

    protected $table = 'REALPRICE';

    public function invoice()
    {
        return $this->belongsTo('App\Invoice', 'SCODE', 'SCODE');
    }

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

    public function transferOutLines()
    {
        return $this->hasMany('App\TransferOutLine', 'REALPRICECODE', 'REALPRICECODE');
    }

    public function reserves()
    {
        return $this->hasMany('App\Reserve', 'REALPRICECODE', 'REALPRICECODE');
    }

    public function pickUps()
    {
        return $this->hasMany('App\PickUp', 'REALPRICECODE', 'REALPRICECODE');
    }
}
