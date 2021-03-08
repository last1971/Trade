<?php

namespace App;

use App\ModelTraits\InsertTrait;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use InsertTrait;

    public $timestamps = false;

    protected $connection = 'firebird';

    protected $fillable = ['NS', 'FIRM_ID', 'POKUPATCODE', 'PRIM', 'STATUS', 'IGK', 'DATA'];

    protected $primaryKey = 'SCODE';

    protected $sequenceName = 'SCODE_GEN';

    protected $table = 'S';

    protected $casts = [
        'SCODE' => 'integer',
        'NS' => 'integer',
    ];

    public function buyer()
    {
        return $this->belongsTo('App\Buyer', 'POKUPATCODE', 'POKUPATCODE');
    }

    public function cashFlows()
    {
        return $this->hasMany('App\CashFlow', 'SCODE', 'SCODE');
    }

    public function employee()
    {
        return $this->belongsTo('App\Employee', 'STAFF_ID', 'ID');
    }

    public function firm()
    {
        return $this->belongsTo('App\Firm', 'FIRM_ID', 'FIRM_ID');
    }

    public function firmHistory()
    {
        return $this->belongsTo('App\FirmHistory', 'FIRMS_HISTORY_ID', 'ID');
    }

    public function invoiceLines()
    {
        return $this->hasMany('App\InvoiceLine', 'SCODE', 'SCODE');
    }

    public function transferOuts()
    {
        return $this->hasMany('App\TransferOut', 'SCODE', 'SCODE');
    }

    public function transferOutLines()
    {
        return $this->hasManyThrough(
            'App\TransferOutLine',
            'App\InvoiceLine',
            'SCODE',
            'REALPRICECODE',
            'SCODE',
            'REALPRICECODE'
        );
    }
}
