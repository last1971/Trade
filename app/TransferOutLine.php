<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TransferOutLine extends Model
{
    //
    public $timestamps = false;

    protected $connection = 'firebird';

    protected $primaryKey = 'REALPRICEFCODE';

    public static $snakeAttributes = false;

    protected $table = 'REALPRICEF';

    public function invoiceLine()
    {
        return $this->belongsTo('App\InvoiceLine', 'REALPRICECODE', 'REALPRICECODE');
    }

    public function transferOut()
    {
        return $this->belongsTo('App\TransferOut', 'SFCODE', 'SFCODE');
    }
}
