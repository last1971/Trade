<?php

namespace App\Services\Notify;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redis;
use Throwable;

/**
 * Доставка в комнату Matrix с защитой: локальный запуск не пишет в боевые комнаты,
 * неудача уходит в очередь повтора, долгое молчание Matrix — одно письмо по теме DEV.
 * Единственное место, где сообщение превращается в текст с тегом инсталляции.
 */
final class MatrixSender
{
    public const RETRY_KEY = 'notify:retry';
    private const DOWN_SINCE_KEY = 'notify:matrix_down_since';
    private const DOWN_ALERTED_KEY = 'notify:matrix_down_alerted';

    public function __construct(private MatrixClient $client)
    {
    }

    /**
     * @param array{subject: string, lines: string[], notice?: bool} $message
     */
    public function send(string $roomId, array $message): bool
    {
        $formatted = $this->format($message);
        // Локальный Trade смотрит в ПРОД-базу, таблица маршрутов = боевые комнаты.
        // Гейт по 'local', а не по 'production': на опте в env опечатка «poduction».
        if (app()->environment('local', 'testing')) {
            Log::info('notify: matrix пропущен (не прод)', ['room' => $roomId, 'body' => $formatted['body']]);
            return true;
        }
        if (!$this->client->configured()) {
            Log::warning('notify: matrix не настроен, сообщение потеряно', ['room' => $roomId, 'subject' => $message['subject']]);
            return false;
        }
        $result = $this->client->send($roomId, $formatted['body'], $formatted['html'], $formatted['notice']);
        if ($result['ok']) {
            $this->markUp();
            return true;
        }
        Log::error('notify: matrix не отправил, в очередь', ['room' => $roomId, 'error' => $result['error'], 'status' => $result['status']]);
        $this->enqueue($roomId, $message);
        $this->markDown();
        return false;
    }

    /** Кнопка «тест» со страницы маршрутов: без гейта и без очереди — человек ждёт ответ. */
    public function sendTest(string $roomId): array
    {
        $formatted = $this->format([
            'subject' => 'Проверка маршрута',
            'lines' => ['Если вы это видите — бот в комнате и токен живой.'],
            'notice' => true,
        ]);
        return $this->client->send($roomId, $formatted['body'], $formatted['html'], true);
    }

    /**
     * Повтор очереди: по одному с головы, первая же неудача возвращает элемент на место и останавливает проход.
     * @return array{sent: int, dropped: int, left: int}
     */
    public function retry(): array
    {
        $sent = 0;
        $dropped = 0;
        $ttl = (int)config('notify.retry.ttl_hours', 24) * 3600;
        if (app()->environment('local', 'testing') || !$this->client->configured()) {
            return ['sent' => 0, 'dropped' => 0, 'left' => (int)Redis::llen(self::RETRY_KEY)];
        }
        while (($raw = Redis::lpop(self::RETRY_KEY)) !== null && $raw !== false) {
            $item = json_decode($raw, true);
            if (!is_array($item) || empty($item['room']) || empty($item['message'])) {
                $dropped++;
                continue;
            }
            if (time() - (int)($item['ts'] ?? 0) > $ttl) {
                Log::warning('notify: повтор просрочен, выброшен', ['room' => $item['room'], 'subject' => $item['message']['subject'] ?? '']);
                $dropped++;
                continue;
            }
            $message = $item['message'];
            $message['subject'] = sprintf('(с %s, доставлено с задержкой) %s', date('H:i', (int)$item['ts']), $message['subject']);
            $formatted = $this->format($message);
            $result = $this->client->send($item['room'], $formatted['body'], $formatted['html'], $formatted['notice']);
            if (!$result['ok']) {
                Redis::lpush(self::RETRY_KEY, $raw);
                $this->markDown();
                break;
            }
            $sent++;
        }
        if ($sent > 0) {
            $this->markUp();
        }
        return ['sent' => $sent, 'dropped' => $dropped, 'left' => (int)Redis::llen(self::RETRY_KEY)];
    }

    /**
     * @return array{body: string, html: string, notice: bool}
     */
    private function format(array $message): array
    {
        $title = trim(NotifyRouter::tag() . ' ' . ($message['subject'] ?? ''));
        $lines = array_values(array_filter(array_map('strval', $message['lines'] ?? []), fn($l) => $l !== ''));
        $max = (int)config('notify.matrix.max_length', 4000);
        $kept = [];
        $length = mb_strlen($title);
        foreach ($lines as $i => $line) {
            if ($length + mb_strlen($line) + 1 > $max) {
                $kept[] = sprintf('…ещё %d строк, полный текст в письме', count($lines) - $i);
                break;
            }
            $kept[] = $line;
            $length += mb_strlen($line) + 1;
        }
        $body = $title . ($kept ? "\n" . implode("\n", $kept) : '');
        $html = '<b>' . e($title) . '</b>' . ($kept ? '<br>' . implode('<br>', array_map('e', $kept)) : '');
        return ['body' => $body, 'html' => $html, 'notice' => (bool)($message['notice'] ?? false)];
    }

    private function enqueue(string $roomId, array $message): void
    {
        try {
            Redis::rpush(self::RETRY_KEY, json_encode(
                ['ts' => time(), 'room' => $roomId, 'message' => $message],
                JSON_UNESCAPED_UNICODE
            ));
        } catch (Throwable $e) {
            Log::error('notify: очередь повтора недоступна: ' . $e->getMessage());
        }
    }

    private function markUp(): void
    {
        try {
            Redis::del(self::DOWN_SINCE_KEY, self::DOWN_ALERTED_KEY);
        } catch (Throwable) {
        }
    }

    /** Первая неудача запоминает время; после N минут — одно письмо по теме DEV, до восстановления второго нет. */
    private function markDown(): void
    {
        try {
            Redis::setnx(self::DOWN_SINCE_KEY, (string)time());
            $since = (int)Redis::get(self::DOWN_SINCE_KEY);
            $minutes = (int)config('notify.retry.down_alert_minutes', 10);
            if (time() - $since < $minutes * 60 || !Redis::setnx(self::DOWN_ALERTED_KEY, '1')) {
                return;
            }
            $addresses = app(NotifyRouter::class)->targets(NotifyRouter::TOPIC_DEV, 'mail');
            if (!$addresses) {
                Log::error('notify: Matrix недоступен с ' . date('d.m.Y H:i', $since) . ', письмо слать некому (нет DEV/mail)');
                return;
            }
            Mail::raw(
                sprintf("Matrix (%s) не отвечает с %s. Сообщения копятся в очереди повтора.", config('notify.matrix.homeserver'), date('d.m.Y H:i', $since)),
                fn($m) => $m->to($addresses)->subject(NotifyRouter::tag() . ' Matrix недоступен')
            );
        } catch (Throwable $e) {
            Log::error('notify: не удалось отметить падение Matrix: ' . $e->getMessage());
        }
    }
}
