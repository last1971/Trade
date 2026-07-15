<?php

namespace App;

use App\ModelTraits\InsertTrait;
use Illuminate\Database\Eloquent\Model;

class GoodClassif extends Model
{
    use InsertTrait;

    public static $snakeAttributes = false;

    public $timestamps = false;

    protected $connection = 'firebird';

    protected $table = 'GOODS_CLASSIF';

    protected $primaryKey = 'ID';

    protected $sequenceName = 'GEN_GOODS_CLASSIF_ID';

    protected $fillable = [
        'GOODSCODE', 'GTIN', 'TNVED', 'OKPD2', 'MARK_REQUIRED',
        'SUPPLIER_INN', 'IS_PRIMARY', 'PRIM', 'UPDATED_AT',
    ];

    public function good()
    {
        return $this->belongsTo('App\Good', 'GOODSCODE', 'GOODSCODE');
    }

    public function markCodesCount(): int
    {
        if (!$this->GTIN) {
            return 0;
        }
        return MarkCode::query()->where('GTIN', $this->GTIN)->count();
    }
}
