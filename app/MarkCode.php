<?php

namespace App;

use App\ModelTraits\InsertTrait;
use Illuminate\Database\Eloquent\Model;

class MarkCode extends Model
{
    use InsertTrait;

    /** STATUS: код выведен из оборота. */
    public const STATUS_RETIRED = 6;

    public static $snakeAttributes = false;

    public $timestamps = false;

    protected $connection = 'firebird';

    protected $table = 'MARKCODES';

    protected $primaryKey = 'MARKCODE';

    protected $sequenceName = 'GEN_MARKCODE';

    protected $fillable = [
        'MARKORDERITEMCODE', 'GOODSCODE', 'GTIN', 'KI', 'KM_FULL',
        'SERIAL_NUMBER', 'SOURCE', 'STATUS', 'TRANSFER_TYPE',
        'SUPPLIER_INN', 'SKLADINCODE', 'SHOPINCODE', 'PR_META_IN_ID',
        'REALPRICECODE', 'REALPRICEFCODE', 'SHOPLOGCODE', 'SPISID',
        'SPISSKLADCODE', 'SPISSHOPCODE', 'RETIRE_REASON', 'RETIRED_AT',
        'QUANTITY',
    ];

    protected $casts = [
        'QUANTITY' => 'integer',
        'PR_META_IN_ID' => 'integer',
        'SPISSKLADCODE' => 'integer',
        'SPISSHOPCODE' => 'integer',
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

    public function storeLine()
    {
        return $this->belongsTo('App\StoreLine', 'SKLADINCODE', 'SKLADINCODE');
    }

    public function spisSklad()
    {
        return $this->belongsTo('App\SpisSklad', 'SPISSKLADCODE', 'SPISSKLADCODE');
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

    public function scopeNotRetired($query)
    {
        return $query->where('STATUS', '<>', self::STATUS_RETIRED);
    }
}
