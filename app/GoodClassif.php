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
        // Снимок карточки Нацкаталога (патч 53) — пишет только NkCardService
        'NK_GOOD_ID', 'NK_STATE', 'NK_STATE_TEXT', 'NK_ATTEMPT', 'NK_FEED_ID', 'NK_SYNC_AT',
    ];

    /**
     * Есть ли таблица в текущей базе: магазинная (magazin.fdb) живёт без
     * GOODS_CLASSIF, и всё про маркировку там должно тихо выключаться.
     * static-мемо — одна проверка на процесс fpm.
     */
    public static function tableExists(): bool
    {
        static $exists = null;
        if ($exists === null) {
            $exists = (bool) \DB::connection('firebird')
                ->select("SELECT 1 FROM RDB\$RELATIONS WHERE RDB\$RELATION_NAME = 'GOODS_CLASSIF'");
        }
        return $exists;
    }

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
