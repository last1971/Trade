<?php

namespace App\Services\Marking;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;

/**
 * HTTP-клиент chz-сервиса (NestJS, X-Api-Key). Единственная точка, знающая адрес, ключ
 * и формат ошибок сервиса: ответ {ok:false, failed:[{reason}]} или HTTP 4xx/5xx → MarkingException
 * с человеческим текстом, чтобы он дошёл до снекбара.
 */
class ChzClient
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => rtrim(config('marking.chz.url'), '/'),
            'timeout' => config('marking.chz.timeout'),
            'headers' => ['X-Api-Key' => config('marking.chz.api_key')],
            'http_errors' => true,
        ]);
    }

    /** GET → декодированный JSON (ok проверяется). */
    public function get(string $path, array $query = []): array
    {
        return $this->json($this->send('GET', $path, ['query' => $query]));
    }

    /** POST JSON → декодированный JSON (ok проверяется). */
    public function post(string $path, array $body, array $headers = []): array
    {
        return $this->json($this->send('POST', $path, ['json' => $body, 'headers' => $headers]));
    }

    /**
     * То же, но ok:false — не ошибка, а состояние: «запрос ещё в полёте» (inProgress),
     * «пачка ещё разбирается» (feedId без gtin). Зовущий разбирает ответ сам; HTTP-ошибки
     * по-прежнему исключение.
     */
    public function getLoose(string $path, array $query = []): array
    {
        return $this->json($this->send('GET', $path, ['query' => $query]), false);
    }

    public function postLoose(string $path, array $body, array $headers = []): array
    {
        return $this->json($this->send('POST', $path, ['json' => $body, 'headers' => $headers]), false);
    }

    /** Сырой ответ (PDF): тело и content-type, JSON-ошибка сервиса разворачивается в исключение. */
    public function raw(string $path, array $query = []): ResponseInterface
    {
        $response = $this->send('GET', $path, ['query' => $query]);
        if (str_starts_with($response->getHeaderLine('Content-Type'), 'application/json')) {
            $this->json($response);
            throw new MarkingException('chz-сервис вернул JSON вместо файла');
        }
        return $response;
    }

    private function send(string $method, string $path, array $options): ResponseInterface
    {
        try {
            return $this->client->request($method, '/api/' . ltrim($path, '/'), $options);
        } catch (BadResponseException $e) {
            $body = json_decode((string)$e->getResponse()->getBody(), true) ?: [];
            $message = $body['message'] ?? $body['failed'][0]['reason'] ?? $e->getMessage();
            $text = 'chz-сервис: ' . (is_array($message) ? implode('; ', $message) : $message);
            if ($e->getResponse()->getStatusCode() === 404) {
                throw new ChzNotFoundException($text);
            }
            throw new MarkingException($text);
        } catch (GuzzleException $e) {
            throw new MarkingException('chz-сервис недоступен: ' . $e->getMessage());
        }
    }

    private function json(ResponseInterface $response, bool $checkOk = true): array
    {
        $data = json_decode((string)$response->getBody(), true);
        if (!is_array($data)) {
            throw new MarkingException('chz-сервис: неразборчивый ответ');
        }
        if ($checkOk && array_key_exists('ok', $data) && !$data['ok']) {
            throw new MarkingException('chz-сервис: ' . ($data['failed'][0]['reason'] ?? 'ошибка'));
        }
        return $data;
    }
}
