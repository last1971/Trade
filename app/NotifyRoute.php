<?php

namespace App;

use App\ModelTraits\InsertTrait;
use Illuminate\Database\Eloquent\Model;

/**
 * Маршрут уведомления: тема → канал → адресат (патч 56, NOTIFY_ROUTE).
 * Таблица одна на базу, значит одна на инсталляцию: её же читает ozon.
 * Тема — «кто реагирует» (OPS, MARKING, PRICES, FINANCE, DEV), список тем живёт в коде.
 */
class NotifyRoute extends Model
{
    use InsertTrait;

    public const CHANNEL_MAIL = 'mail';
    public const CHANNEL_MATRIX = 'matrix';

    public const CHANNELS = [self::CHANNEL_MAIL, self::CHANNEL_MATRIX];

    /** Внутренний id комнаты Matrix: «!xxx:server». Алиасы «#name:server» не принимаем — их надо резолвить. */
    public const MATRIX_ROOM_RE = '/^![A-Za-z0-9._=\-]+:[A-Za-z0-9.-]+$/';

    public static $snakeAttributes = false;

    public $timestamps = false;

    protected $connection = 'firebird';

    protected $table = 'NOTIFY_ROUTE';

    protected $primaryKey = 'ID';

    protected $sequenceName = 'GEN_NOTIFY_ROUTE';

    protected $fillable = ['TOPIC', 'CHANNEL', 'TARGET', 'ENABLED'];

    protected $casts = [
        'ID' => 'integer',
        'ENABLED' => 'integer',
    ];

    /** Firebird отдаёт CHAR/VARCHAR с хвостом пробелов — режем на входе, чтобы сравнения не врали. */
    public function getTopicAttribute($v)
    {
        return $v === null ? null : trim((string)$v);
    }

    public function getChannelAttribute($v)
    {
        return $v === null ? null : trim((string)$v);
    }

    public function getTargetAttribute($v)
    {
        return $v === null ? null : trim((string)$v);
    }

    public function setTopicAttribute($v)
    {
        $this->attributes['TOPIC'] = strtoupper(trim((string)$v));
    }

    public function setTargetAttribute($v)
    {
        $this->attributes['TARGET'] = trim((string)$v);
    }
}
