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
        return $this->belongsTo('App\Firm', 'FIRM_ID', 'FIRM_ID');
    }

    public function invoice()
    {
        return $this->belongsTo('App\Invoice', 'SCODE', 'SCODE');
    }

    public function transferOutLines()
    {
        return $this->hasMany('App\TransferOutLine', 'SFCODE', 'SFCODE');
    }
}
