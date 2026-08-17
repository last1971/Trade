<?php

namespace App\Notifications;

use App\TransferOut;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * УПД напечатан, а коды маркировки по нему из оборота ещё не выведены.
 * Покупателю коды не передаются, значит вывести их должны мы — напоминание об этом.
 */
class MarkCodesNotRetiredNotification extends Notification
{
    use Queueable;

    public function __construct(private TransferOut $transferOut, private int $codesCount)
    {
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $number = $this->transferOut->NSF;
        $date = Carbon::parse($this->transferOut->DATDOK ?: $this->transferOut->DATA)->format('d.m.Y');
        $buyer = trim((string)$this->transferOut->buyer->SHORTNAME);

        return (new MailMessage())
            ->subject("Передайте коды ЧЗ — УПД № {$number}")
            ->line("Напечатан УПД № {$number} от {$date}, покупатель: {$buyer}.")
            ->line('Покупатель не участник оборота ЧЗ — коды маркировки ему не передаются.')
            ->line("Из оборота ещё не выведено кодов: {$this->codesCount}.")
            ->line('Передайте коды ЧЗ.');
    }

    public function toArray($notifiable)
    {
        return [];
    }
}
