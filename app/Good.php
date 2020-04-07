<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Good extends Model
{
    //
    public $timestamps = false;

    protected $connection = 'firebird';

    protected $primaryKey = 'GOODSCODE';

    protected $table = 'GOODS';

    public function category()
    {
        return $this->belongsTo('App\Category', 'CATEGORYCODE', 'CATEGORYCODE');
    }

    public function name()
    {
        return $this->belongsTo('App\Name', 'NAMECODE', 'NAMECODE');
    }

    public function invoiceLines()
    {
        return $this->hasMany('App\InvoiceLine', 'GOODSCODE', 'GOODSCODE');
    }

    public function transferOutLines()
    {
        return $this->hasMany('App\TransferOutLine', 'GOODSCODE', 'GOODSCODE');
    }

    public function reserves()
    {
        return $this->hasMany('App\Reserve', 'GOODSCODE', 'GOODSCODE');
    }

    public function pickUps()
    {
        return $this->hasMany('App\PickUp', 'GOODSCODE', 'GOODSCODE');
    }
}
