<?php

namespace App;

use App\ModelTraits\InsertTrait;
use Illuminate\Database\Eloquent\Model;

class MarkCode extends Model
{
    use InsertTrait;

    public static $snakeAttributes = false;

    public $timestamps = false;

    protected $connection = 'firebird';

    protected $table = 'MARKCODES';

    protected $primaryKey = 'MARKCODE';

    protected $sequenceName = 'GEN_MARKCODE';

    protected $fillable = [
        'MARKORDERITEMCODE', 'GOODSCODE', 'GTIN', 'KI', 'KM_FULL',
        'SERIAL_NUMBER', 'SOURCE', 'STATUS', 'TRANSFER_TYPE',
        'SUPPLIER_INN', 'SKLADINCODE', 'SHOPINCODE',
        'REALPRICECODE', 'REALPRICEFCODE', 'SHOPLOGCODE', 'SPISID',
    ];

    public function good()
    {
        return $this->belongsTo('App\Good', 'GOODSCODE', 'GOODSCODE');
    }

    public function invoiceLine()
    {
        return $this->belongsTo('App\InvoiceLine', 'REALPRICECODE', 'REALPRICECODE');
    }

    public function transferOutLine()
    {
        return $this->belongsTo('App\TransferOutLine', 'REALPRICEFCODE', 'REALPRICEFCODE');
    }

    public function scopeFree($query)
    {
        return $query->where('STATUS', 5)->whereNull('REALPRICECODE');
    }

    public function scopeForGood($query, int $goodsCode)
    {
        return $query->where('GOODSCODE', $goodsCode);
    }

    public function scopeAttachedToInvoiceLine($query, int $realPriceCode)
    {
        return $query->where('REALPRICECODE', $realPriceCode);
    }

    public function scopeAttachedToTransferOutLine($query, int $realPriceFCode)
    {
        return $query->where('REALPRICEFCODE', $realPriceFCode);
    }
}
