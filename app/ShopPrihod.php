<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Приходный документ магазина. Номер накладной, дата документа, ГТД и страна
 * лежат здесь, а не в SHOPIN: строка движения ссылается сюда через SHOPINPRCODE.
 * На складе те же поля лежат прямо в SKLADIN, отдельной таблицы нет.
 */
class ShopPrihod extends Model
{
    public $timestamps = false;

    protected $connection = 'firebird';

    protected $primaryKey = 'SHOPINPRCODE';

    protected $table = 'SHOPINPR';
}
