<?php

namespace App;

use App\ModelTraits\InsertTrait;
use Illuminate\Database\Eloquent\Model;

class InvoiceLine extends Model
{
    use InsertTrait;

    public $timestamps = false;

    protected $connection = 'firebird';

    protected $fillable = ['SCODE', 'GOODSCODE', 'QUAN', 'PRICE', 'SUMMAP', 'PRIM', 'WHERE_ORDERED'];

    protected $primaryKey = 'REALPRICECODE';

    protected $sequenceName = 'REALPRICECODE_GEN';

    protected $table = 'REALPRICE';

//    protected $casts = [
//        'PRICE' => 'double',
//        'SUMMAP' => 'double',
//    ];

    public function category()
    {
        return $this->hasOneThrough(
            'App\Category',
            'App\Good',
            'GOODSCODE',
            'CATEGORYCODE',
            'GOODSCODE',
            'CATEGORYCODE'
        );
    }

    public function invoice()
    {
        return $this->belongsTo('App\Invoice', 'SCODE', 'SCODE');
    }

    public function good()
    {
        return $this->belongsTo('App\Good', 'GOODSCODE', 'GOODSCODE');
    }

    public function name()
    {
        return $this->hasOneThrough(
            'App\Name',
            'App\Good',
            'GOODSCODE',
            'NAMECODE',
            'GOODSCODE',
            'NAMECODE'
        );
    }

    public function transferOutLines()
    {
        return $this->hasMany('App\TransferOutLine', 'REALPRICECODE', 'REALPRICECODE');
    }

    public function reserves()
    {
        return $this->hasMany('App\Reserve', 'REALPRICECODE', 'REALPRICECODE');
    }

    public function pickUps()
    {
        return $this->hasMany('App\PickUp', 'REALPRICECODE', 'REALPRICECODE');
    }

    public function orderLinesTransit()
    {
        return $this->hasMany('App\OrderLine', 'GOODSCODE', 'GOODSCODE')
            ->join('ZAKAZ_MASTER', 'ZAKAZ_MASTER.ID', '=', 'MASTER_ID')
            ->whereIn('ZAKAZ_MASTER.STATUS', [2, 3]);
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
}
