<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    //
    public $timestamps = false;
    protected $connection = 'firebird';
    protected $dates = ['DATA'];
    protected $table = 'S';

    public function buyer()
    {
        return $this->belongsTo('App\Buyer', 'POKUPATCODE', 'POKUPATCODE');
    }

    public function employee()
    {
        return $this->belongsTo('App\Employee', 'STAFF_ID', 'ID');
    }

    public function firm()
    {
        return $this->belongsTo('App\FIRM', 'FIRM_ID', 'FIRM_ID');
    }

    public function invoiceLines()
    {
        return $this->hasMany('App\InvoiceLine', 'SCODE', 'SCODE');
    }

    public function invoiceLinesSum()
    {
        return $this;
    }
}
