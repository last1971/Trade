<?php

namespace App\Services\Marking;

use App\Notifications\MarkCodesNotRetiredNotification;
use App\Services\Notify\NotifyRouter;
use App\TransferOut;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Напоминание о непогашенных кодах ЧЗ при печати УПД.
 * Шлём, только если покупателю коды не передаются: тогда вывести их из оборота — наша задача.
 * Не чаще раза в сутки на документ, чтобы повторная печать не плодила писем.
 */
class MarkCodesReminder
{
    private const THROTTLE_KEY = 'chz-remind-';

    public function __construct(private NotifyRouter $router)
    {
    }

    public function remindIfNeeded(TransferOut $transferOut): void
    {
        if ($transferOut->buyer->transfersMarkCodes()) {
            return;
        }

        $count = $transferOut->markCodes()->notRetired()->count();
        if ($count === 0) {
            return;
        }

        $notifiable = $this->router->notifiable(NotifyRouter::TOPIC_MARKING);
        if (!$notifiable) {
            return;
        }

        if (!Cache::add(self::THROTTLE_KEY . $transferOut->SFCODE, true, now()->addDay())) {
            return;
        }

        try {
            $notifiable->notify(new MarkCodesNotRetiredNotification($transferOut, $count));
        } catch (Throwable $e) {
            // печать УПД важнее письма — не роняем её
            Log::error('MarkCodesReminder: уведомление не отправлено', [
                'SFCODE' => $transferOut->SFCODE,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
