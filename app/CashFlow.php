<?php

namespace App;

use App\ModelTraits\InsertTrait;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class CashFlow extends Model
{
    use InsertTrait;
    public static $snakeAttributes = false;

    public $timestamps = false;

    protected $fillable = ['MONEYSCHET', 'NS', 'DATA', 'POKUPATCODE', 'NPP', 'SCODE', 'PRIM', 'SFCODE1', 'USERNAME'];

    protected $connection = 'firebird';

    protected $primaryKey = 'SCHETCODE';

    protected $sequenceName = 'SCHETCODE_GEN';

    protected $table = 'SCHET';

    protected function MONEYSCHET(): Attribute
    {
        return Attribute::set(fn($value) => floatval($value));
    }
    public function invoice()
    {
        return $this->belongsTo('App\Invoice', 'SCODE', 'SCODE');
    }

    public function transferOut()
    {
        return $this->belongsTo('App\TransferOut', 'SFCODE1', 'SFCODE');
    }

    public function order()
    {
        return $this->belongsTo('App\Order', 'ZAKAZ_MASTER_ID', 'ID');
    }
}
