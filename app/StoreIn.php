<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Документ прихода на склад: группа строк SKLADIN с одним номером NP.
 * Таблицы-шапки в Firebird нет — модель читается из derived-запроса
 * (StoreInController задаёт setRawFrom с группировкой по NP).
 */
class StoreIn extends Model
{
    //
    public $timestamps = false;

    protected $connection = 'firebird';

    public $incrementing = false;

    protected $primaryKey = 'NP';

    public function seller()
    {
        return $this->belongsTo(Seller::class, 'WHEREISPOSTCODE', 'WHEREISPOSTCODE');
    }

    public function storeLines()
    {
        return $this->hasMany(StoreLine::class, 'NP', 'NP');
    }
}
