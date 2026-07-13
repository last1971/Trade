<?php

namespace App;

use App\ModelTraits\InsertTrait;
use Illuminate\Database\Eloquent\Model;

class GoodName extends Model
{
    use InsertTrait;

    public $timestamps = false;

    protected $connection = 'firebird';

    protected $fillable = ['GOODSCODE', 'NAME'];

    protected $primaryKey = 'ID';

    protected $table = 'GOOD_NAMES';

    protected $sequenceName = 'GEN_GOODS_SEARCH_ID';

    public static function normalize($value)
    {
        return mb_ereg_replace(config('app.search_replace'), '', $value);
    }

    public function good()
    {
        return $this->belongsTo('App\Good', 'GOODSCODE', 'GOODSCODE');
    }

}
