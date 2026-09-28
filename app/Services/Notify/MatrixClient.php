<?php

namespace App\Services\Notify;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Throwable;

/**
 * Один HTTP-вызов Client-Server API Matrix от имени бота. Процесс бота
 * (matrix_bot на домашнем маке) в отправке не участвует — только homeserver.
 * Ничего не знает ни о маршрутах, ни о повторах: это MatrixSender.
 */
final class MatrixClient
{
    private ?string $homeserver;
    private ?string $token;
    private int $timeout;

    public function __construct()
    {
        $this->homeserver = rtrim((string)config('notify.matrix.homeserver'), '/') ?: null;
        $this->token = config('notify.matrix.token') ?: null;
        $this->timeout = (int)config('notify.matrix.timeout', 5);
    }

    public function configured(): bool
    {
        return $this->homeserver !== null && $this->token !== null;
    }

    /**
     * @return array{ok: bool, status: ?int, error: ?string}
     */
    public function send(string $roomId, string $body, ?string $html = null, bool $notice = false): array
    {
        if (!$this->configured()) {
            return ['ok' => false, 'status' => null, 'error' => 'MATRIX_HOMESERVER / MATRIX_ACCESS_TOKEN не заданы'];
        }
        $content = ['msgtype' => $notice ? 'm.notice' : 'm.text', 'body' => $body];
        if ($html !== null) {
            $content['format'] = 'org.matrix.custom.html';
            $content['formatted_body'] = $html;
        }
        $txn = 'trade-' . (string)microtime(true) . '-' . bin2hex(random_bytes(4));
        $url = sprintf(
            '%s/_matrix/client/v3/rooms/%s/send/m.room.message/%s',
            $this->homeserver,
            rawurlencode($roomId),
            $txn
        );
        try {
            $response = (new Client(['timeout' => $this->timeout, 'connect_timeout' => $this->timeout]))
                ->put($url, [
                    'headers' => ['Authorization' => 'Bearer ' . $this->token],
                    'json' => $content,
                ]);
            return ['ok' => true, 'status' => $response->getStatusCode(), 'error' => null];
        } catch (RequestException $e) {
            $status = $e->getResponse() ? $e->getResponse()->getStatusCode() : null;
            $text = $e->getResponse() ? (string)$e->getResponse()->getBody() : $e->getMessage();
            return ['ok' => false, 'status' => $status, 'error' => mb_substr($text, 0, 300)];
        } catch (Throwable $e) {
            return ['ok' => false, 'status' => null, 'error' => $e->getMessage()];
        }
    }
}
