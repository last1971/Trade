<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TransferOutLine extends Model
{
    //
    public $timestamps = false;

    protected $connection = 'firebird';

    protected $primaryKey = 'REALPRICEFCODE';

    protected $table = 'REALPRICEF';

    public function invoiceLine()
    {
        return $this->belongsTo('App\InvoiceLine', 'REALPRICECODE', 'REALPRICECODE');
    }
}
