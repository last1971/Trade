<?php

namespace App\Services\Notify;

use App\Notifications\Channels\MatrixChannel;
use App\NotifyRoute;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Единственное место, где тема превращается в адресатов. Таблица NOTIFY_ROUTE
 * читается на каждой отправке (их единицы в час), кэша нет — правка строки
 * действует сразу. Тема без включённых строк уходит в никуда, с записью в лог.
 */
final class NotifyRouter
{
    public const TOPIC_OPS = 'OPS';
    public const TOPIC_MARKING = 'MARKING';
    public const TOPIC_PRICES = 'PRICES';
    public const TOPIC_FINANCE = 'FINANCE';
    public const TOPIC_DEV = 'DEV';

    /** Тег инсталляции в заголовке письма и сообщения — одинаковый с ozon. */
    public static function tag(): string
    {
        return config('marking.stock_mode') === 'shop' ? '[розница]' : '[опт]';
    }

    /** @return string[] включённые адресаты темы по каналу */
    public function targets(string $topic, string $channel): array
    {
        return NotifyRoute::query()
            ->where('TOPIC', strtoupper($topic))
            ->where('CHANNEL', $channel)
            ->where('ENABLED', 1)
            ->get()
            ->map(fn(NotifyRoute $r) => $r->TARGET)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /** Адресат «по требованию» со всеми каналами темы; null — темы нет ни у кого. */
    public function notifiable(string $topic): ?AnonymousNotifiable
    {
        $notifiable = new AnonymousNotifiable();
        $any = false;
        foreach (NotifyRoute::CHANNELS as $channel) {
            $targets = $this->targets($topic, $channel);
            if ($targets) {
                $notifiable->route($channel, $targets);
                $any = true;
            }
        }
        if (!$any) {
            Log::warning('notify: у темы нет маршрутов', ['topic' => $topic]);
            return null;
        }
        return $notifiable;
    }

    /** true — ушло хотя бы в один канал; исключения транспорта не выпускаем наружу. */
    public function notify(string $topic, Notification $notification): bool
    {
        $notifiable = $this->notifiable($topic);
        if (!$notifiable) {
            return false;
        }
        try {
            $notifiable->notify($notification);
            return true;
        } catch (Throwable $e) {
            Log::error('notify: не отправлено', ['topic' => $topic, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /** Имя канала Laravel по CHANNEL таблицы. */
    public static function channelClass(string $channel): string
    {
        return $channel === NotifyRoute::CHANNEL_MATRIX ? MatrixChannel::class : $channel;
    }
}
