<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Good extends Model
{
    //
    public $timestamps = false;

    protected $connection = 'firebird';

    protected $primaryKey = 'GOODSCODE';

    protected $table = 'GOODS';

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

    public function invoiceLines()
    {
        return $this->hasMany('App\InvoiceLine', 'GOODSCODE', 'GOODSCODE');
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

    public function reserves()
    {
        return $this->hasMany('App\Reserve', 'GOODSCODE', 'GOODSCODE');
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

    public function pickUps()
    {
        return $this->hasMany('App\PickUp', 'GOODSCODE', 'GOODSCODE');
    }

    public function warehouse()
    {
        return $this->hasOne('App\Warehouse', 'GOODSCODE', 'GOODSCODE');
    }
}
