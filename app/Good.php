<?php

namespace App;

use App\ModelTraits\InsertTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Good extends Model
{
    use InsertTrait;

    public static $snakeAttributes = false;

    public $timestamps = false;

    protected $connection = 'firebird';

    protected $fillable = ['NAMECODE', 'CATEGORYCODE', 'UNIT_I', 'BODY', 'PRODUCER', 'PRIM', 'YEARP'];

    protected $primaryKey = 'GOODSCODE';

    protected $sequenceName = 'GOODSCODE_GEN';

    protected $table = 'GOODS';

    public function setBODYAttribute($value)
    {
        $this->attributes['BODY'] = $value ?? '';
    }

    public function setPRODUCERAttribute($value)
    {
        $this->attributes['PRODUCER'] = $value ?? '';
    }

    public function setPRIMAttribute($value)
    {
        $this->attributes['PRIM'] = $value ?? '';
    }

    public function getUnitCodeAttribute()
    {
        return config('unit_codes')[trim(Str::upper($this->getAttributes()['UNIT_I']))];
    }

    public function getUnitNameAttribute()
    {
        return config('unit')[$this->unitCode];
    }

    public function category()
    {
        return $this->belongsTo('App\Category', 'CATEGORYCODE', 'CATEGORYCODE');
    }

    public function goodNames()
    {
        return $this->hasMany('App\GoodName', 'GOODSCODE', 'GOODSCODE');
    }

    public function invoiceLines()
    {
        return $this->hasMany('App\InvoiceLine', 'GOODSCODE', 'GOODSCODE');
    }

    public function invoiceLinesTransit()
    {
        return $this->hasMany('App\InvoiceLine', 'GOODSCODE', 'GOODSCODE')
            ->join('S', 'S.SCODE', '=', 'REALPRICE.SCODE')
            ->whereIn('S.STATUS', [2, 3]);
    }

    public function name()
    {
        return $this->belongsTo('App\Name', 'NAMECODE', 'NAMECODE');
    }

    public function orderLines()
    {
        return $this->hasMany('App\OrderLine', 'GOODSCODE', 'GOODSCODE');
    }

    public function orderLinesTransit()
    {
        return $this->hasMany('App\OrderLine', 'GOODSCODE', 'GOODSCODE')
            ->join('ZAKAZ_MASTER', 'ZAKAZ_MASTER.ID', '=', 'MASTER_ID')
            ->whereIn('ZAKAZ_MASTER.STATUS', [2, 3]);
    }

    public function orderStep()
    {
        return $this->hasOne('App\OrderStep', 'GOODSCODE', 'GOODSCODE');
    }

    public function pickUps()
    {
        return $this->hasMany('App\PickUp', 'GOODSCODE', 'GOODSCODE');
    }

    public function pickUpsTransit()
    {
        return $this->hasManyThrough(
            'App\PickUp',
            'App\InvoiceLine',
            'GOODSCODE',
            'REALPRICECODE',
            'GOODSCODE',
            'REALPRICECODE'
        )
            ->join('S', 'S.SCODE', '=', 'REALPRICE.SCODE')
            ->whereIn('S.STATUS', [2, 3]);
    }

    public function retailOrderLines()
    {
        return $this->hasMany('App\RetailOrderLine', 'GOODSCODE', 'GOODSCODE');
    }

    public function retailOrderLinesTransit()
    {
        return $this->hasMany('App\RetailOrderLine', 'GOODSCODE', 'GOODSCODE')
            ->whereIn('STATUS', [1, 2, 3, 5, 6]);
    }

    public function reserves()
    {
        return $this->hasMany('App\Reserve', 'GOODSCODE', 'GOODSCODE');
    }

    public function reservesTransit()
    {
        return $this->hasManyThrough(
            'App\Reserve',
            'App\InvoiceLine',
            'GOODSCODE',
            'REALPRICECODE',
            'GOODSCODE',
            'REALPRICECODE'
        )
            ->join('S', 'S.SCODE', '=', 'REALPRICE.SCODE')
            ->whereIn('S.STATUS', [2, 3]);
    }

    public function retailPrice()
    {
        return $this->hasOne('App\RetailPrice', 'GOODSCODE', 'GOODSCODE');
    }

    public function retailStore()
    {
        return $this->hasOne('App\RetailStore', 'GOODSCODE', 'GOODSCODE');
    }

    public function shopLines()
    {
        return $this->hasMany('App\ShopLine', 'GOODSCODE', 'GOODSCODE');
    }

    public function shopLinesTransit()
    {
        return $this->hasManyThrough(
            'App\ShopLine',
            'App\OrderLine',
            'GOODSCODE',
            'ZAKAZ_DETAIL_ID',
            'GOODSCODE',
            'ID'
        )
            ->join('ZAKAZ_MASTER', 'ZAKAZ_MASTER.ID', '=', 'MASTER_ID')
            ->whereIn('ZAKAZ_MASTER.STATUS', [2, 3]);
    }

    public function storeLines()
    {
        return $this->hasMany('App\StoreLine', 'GOODSCODE', 'GOODSCODE');
    }

    public function storeLinesTransit()
    {
        return $this->hasManyThrough(
            'App\StoreLine',
            'App\OrderLine',
            'GOODSCODE',
            'ZAKAZ_DETAIL_ID',
            'GOODSCODE',
            'ID'
        )
            ->join('ZAKAZ_MASTER', 'ZAKAZ_MASTER.ID', '=', 'MASTER_ID')
            ->whereIn('ZAKAZ_MASTER.STATUS', [2, 3]);
    }

    public function transferOutLines()
    {
        return $this->hasMany('App\TransferOutLine', 'GOODSCODE', 'GOODSCODE');
    }

    public function warehouse()
    {
        return $this->hasOne('App\Warehouse', 'GOODSCODE', 'GOODSCODE');
    }
}
