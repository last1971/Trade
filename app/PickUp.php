<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PickUp extends Model
{
    //
    public $timestamps = false;

    protected $connection = 'firebird';

    protected $primaryKey = 'PODBPOSCODE';

    protected $table = 'PODBPOS';

    public function good()
    {
        return $this->belongsTo('App\Good', 'GOODSCODE', 'GOODSCODE');
    }

    public function invoiceLine()
    {
        return $this->belongsTo('App\InvoiceLine', 'REALPRICECODE', 'REALPRICECODE');
    }
}
