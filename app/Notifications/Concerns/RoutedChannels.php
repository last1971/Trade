<?php

namespace App\Notifications\Concerns;

use App\NotifyRoute;
use App\Services\Notify\NotifyRouter;

/**
 * Каналы уведомления = те, для которых у адресата есть маршрут.
 * Текст для Matrix собирается из того же MailMessage, что и письмо, —
 * второго описания сообщения нет.
 */
trait RoutedChannels
{
    public function via($notifiable): array
    {
        $via = [];
        foreach (NotifyRoute::CHANNELS as $channel) {
            if (!empty($notifiable->routeNotificationFor($channel, $this))) {
                $via[] = NotifyRouter::channelClass($channel);
            }
        }
        return $via;
    }

    /** @return array{subject: string, lines: string[], notice: bool} */
    public function toMatrix($notifiable): array
    {
        $mail = $this->toMail($notifiable);
        return [
            'subject' => (string)$mail->subject,
            'lines' => array_map('strval', array_merge($mail->introLines, $mail->outroLines)),
            'notice' => property_exists($this, 'matrixNotice') ? (bool)$this->matrixNotice : false,
        ];
    }
}
