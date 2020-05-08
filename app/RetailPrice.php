<?php

namespace App;

use App\ModelTraits\InsertTrait;
use Illuminate\Database\Eloquent\Model;

class RetailPrice extends Model
{
    use InsertTrait;

    public $timestamps = false;

    protected $connection = 'firebird';

    protected $fillable = ['PRICEROZN', 'PRICEMOPT', 'PRICEOPT', 'QUANMOPT', 'QUANOPT', 'DOLLAR', 'GOODSCODE'];

    protected $primaryKey = 'PRICECODE';

    protected $sequenceName = 'PRICECODE_GEN';

    protected $table = 'PRICE';

    public function good()
    {
        return $this->belongsTo('App\Good', 'GOODSCODE', 'GOODSCODE');
    }
}
