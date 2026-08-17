<?php

namespace App\Services\Marking;

use App\Notifications\MarkCodesNotRetiredNotification;
use App\TransferOut;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * Напоминание о непогашенных кодах ЧЗ при печати УПД.
 * Шлём, только если покупателю коды не передаются: тогда вывести их из оборота — наша задача.
 * Не чаще раза в сутки на документ, чтобы повторная печать не плодила писем.
 */
class MarkCodesReminder
{
    private const THROTTLE_KEY = 'chz-remind-';

    public function remindIfNeeded(TransferOut $transferOut): void
    {
        $address = config('mail.chz_notify');
        if (!$address || $transferOut->buyer->transfersMarkCodes()) {
            return;
        }

        $count = $transferOut->markCodes()->notRetired()->count();
        if ($count === 0) {
            return;
        }

        if (!Cache::add(self::THROTTLE_KEY . $transferOut->SFCODE, true, now()->addDay())) {
            return;
        }

        try {
            Notification::route('mail', $address)
                ->notify(new MarkCodesNotRetiredNotification($transferOut, $count));
        } catch (Throwable $e) {
            // печать УПД важнее письма — не роняем её
            Log::error('MarkCodesReminder: письмо не отправлено', [
                'SFCODE' => $transferOut->SFCODE,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
