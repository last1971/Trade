<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Документ прихода: группа строк с одним номером NP. На опте это строки SKLADIN,
 * в магазине — SHOPIN (там же и ведутся приходы розницы). Таблицы-шапки в Firebird
 * нет — модель читается из derived-запроса (StoreInController задаёт setRawFrom).
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
        return $this->hasMany(
            config('app.is_shop') ? ShopLine::class : StoreLine::class,
            'NP',
            'NP'
        );
    }
}
