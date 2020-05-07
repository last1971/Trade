<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FirmHistory extends Model
{
    public $timestamps = false;

    protected $connection = 'firebird';

    protected $primaryKey = 'ID';

    protected $table = 'FIRMS_HISTORY';

    public function firm()
    {
        return $this->belongsTo('App\Firm', 'FIRM_ID', 'FIRM_ID');
    }

    public function invoices()
    {
        return $this->hasMany('App\Invoice', 'FIRMS_HISTORY_ID', 'ID');
    }

    public function transferOuts()
    {
        return $this->hasMany('App\TransferOut', 'FIRMS_HISTORY_ID', 'ID');
    }
}
