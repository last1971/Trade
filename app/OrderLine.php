<?php

namespace App;

use App\ModelTraits\InsertTrait;
use Illuminate\Database\Eloquent\Model;

class OrderLine extends Model
{
    use InsertTrait;

    public $fillable = [
        'MASTER_ID', 'GOODSCODE', 'QUAN', 'NAME_IN_PRICE', 'GTD', 'STRANA', 'STAFF_ID', 'PRIM', 'PRICE', 'SUMMAP'
    ];

    public $timestamps = false;

    protected $connection = 'firebird';

    protected $primaryKey = 'ID';

    protected $sequenceName = 'GEN_ZAKAZ_DETAIL_ID';

    protected $table = 'ZAKAZ_DETAIL';

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

    public function order()
    {
        return $this->belongsTo('App\Order', 'MASTER_ID', 'ID');
    }

    public function seller()
    {
        return $this->hasOneThrough(
            'App\Seller',
            'App\Order',
            'ID',
            'WHEREISPOSTCODE',
            'MASTER_ID',
            'WHEREISPOSTCODE'
        );
    }

    public function shopLines()
    {
        return $this->hasMany('App\ShopLine', 'ZAKAZ_DETAIL_ID', 'ID');
    }

    public function storeLines()
    {
        return $this->hasMany('App\StoreLine', 'ZAKAZ_DETAIL_ID', 'ID');
    }

}
