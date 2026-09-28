<?php

namespace App\Notifications\Channels;

use App\Services\Notify\MatrixSender;
use Illuminate\Notifications\Notification;

/**
 * Канал Laravel Notifications для Matrix. Комнаты берёт из
 * routeNotificationFor('matrix') — их кладёт NotifyRouter из NOTIFY_ROUTE.
 * Уведомление отдаёт toMatrix(): ['subject', 'lines', 'notice'].
 */
class MatrixChannel
{
    public function __construct(private MatrixSender $sender)
    {
    }

    public function send($notifiable, Notification $notification): void
    {
        $rooms = $notifiable->routeNotificationFor('matrix', $notification);
        if (!$rooms || !method_exists($notification, 'toMatrix')) {
            return;
        }
        $message = $notification->toMatrix($notifiable);
        foreach ((array)$rooms as $room) {
            $this->sender->send($room, $message);
        }
    }
}
