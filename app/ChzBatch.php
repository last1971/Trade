<?php

namespace App;

use App\ModelTraits\InsertTrait;
use Illuminate\Database\Eloquent\Model;

/**
 * Пачка кодов для Честного знака — список КИ, уходящий одним документом.
 * Таблица общая с ручными выгрузками (патч 39, вкладка «ЧЗ» в админке ozon):
 * пачки без статуса ведёт человек, пачки со статусом — воркер chz:outbox.
 *
 * Сами коды здесь не лежат: в CHZ_BATCH_KI только КИ, а полный КМ, количество
 * и родителя воркер берёт из MARKCODES и MARKCODES_REMARK в момент отправки.
 */
class ChzBatch extends Model
{
    use InsertTrait;

    /** Ручная выгрузка файлом: вывод из оборота, возврат, вывод по УПД. */
    public const KIND_RETIRE = 'retire';
    public const KIND_RETURN = 'return';
    public const KIND_RETIRE_UPD = 'retire_upd';
    /** Автоматическая отправка: деление (оно же нанесение), нанесение, ввод в оборот. */
    public const KIND_DIVISION = 'division';
    public const KIND_APPLY = 'apply';
    public const KIND_INTRO = 'intro';

    /** STATUS = NULL — ручная пачка, воркер её не видит. */
    public const STATUS_READY = 'READY';
    public const STATUS_WAIT = 'WAIT';
    public const STATUS_SENT = 'SENT';
    public const STATUS_DONE = 'DONE';
    public const STATUS_ERROR = 'ERROR';

    public static $snakeAttributes = false;

    public $timestamps = false;

    protected $connection = 'firebird';

    protected $table = 'CHZ_BATCH';

    protected $primaryKey = 'ID';

    protected $sequenceName = 'GEN_CHZ_BATCH';

    protected $fillable = [
        'KIND', 'CNT', 'SFCODE', 'DOC_UUID', 'STATUS', 'REPORT_ID',
        'ERROR_TEXT', 'PARENT_ID', 'SCODE', 'CREATED_BY', 'SENT_AT', 'CONFIRMED_AT',
    ];

    protected $casts = [
        'ID' => 'integer',
        'CNT' => 'integer',
        'SFCODE' => 'integer',
        'PARENT_ID' => 'integer',
        'SCODE' => 'integer',
    ];

    /** Документ пачки: у вывода по продаже — счёт, у вывода по УПД — она сама. */
    public function invoice()
    {
        return $this->belongsTo('App\Invoice', 'SCODE', 'SCODE');
    }

    public function transferOut()
    {
        return $this->belongsTo('App\TransferOut', 'SFCODE', 'SFCODE');
    }

    /** КИ пачки в порядке добавления. */
    public function kis(): array
    {
        return $this->getConnection()
            ->table('CHZ_BATCH_KI')
            ->where('BATCH_ID', $this->ID)
            ->pluck('KI')
            ->map(fn($ki) => trim((string)$ki))
            ->all();
    }
}
