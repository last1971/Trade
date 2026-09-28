<?php

namespace App\Http\Controllers\Api;

use App\NotifyRoute;
use App\Services\Notify\MatrixSender;
use App\Services\NotifyRouteService;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Страница «Маршруты уведомлений»: список/создание/правка — общим механизмом,
 * плюс «тест»: пробное сообщение адресату. Регулярка на комнату не докажет,
 * что бот в ней состоит, — только живая отправка.
 */
class NotifyRouteController extends ModelController
{
    public function __construct()
    {
        parent::__construct(NotifyRouteService::class);
    }

    public function test(int $id, MatrixSender $sender)
    {
        $route = NotifyRoute::findOrFail($id);
        if ($route->CHANNEL === NotifyRoute::CHANNEL_MATRIX) {
            $result = $sender->sendTest($route->TARGET);
            return [
                'ok' => $result['ok'],
                'message' => $result['ok']
                    ? 'Сообщение в комнате'
                    : sprintf('Matrix ответил %s: %s', $result['status'] ?? '—', $result['error']),
            ];
        }
        try {
            Mail::raw(
                'Если вы это видите — маршрут ' . $route->TOPIC . ' работает.',
                fn($m) => $m->to($route->TARGET)->subject('Проверка маршрута уведомлений')
            );
            return ['ok' => true, 'message' => 'Письмо отправлено'];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => 'Почта не отправила: ' . $e->getMessage()];
        }
    }
}
