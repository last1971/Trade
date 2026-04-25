<?php

namespace App\Services\Edo\Providers;

use App\Services\Edo\Contracts\EdoException;
use App\Services\Edo\Contracts\EdoMessageDto;
use App\Services\Edo\Contracts\EdoProviderInterface;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ServerException;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Psr\SimpleCache\InvalidArgumentException;

class SbisProvider implements EdoProviderInterface
{
    private const SERVICE_URL = 'https://online.sbis.ru/service/?srv=1';
    private const AUTH_URL = 'https://online.sbis.ru/auth/service/';

    private Client $client;
    private string $sessionId;
    private array $headers;

    /**
     * @throws GuzzleException
     * @throws InvalidArgumentException
     */
    public function __construct()
    {
        try {
            $this->client = new Client();
            $this->sessionId = cache()->has('sbis_key') ? cache()->get('sbis_key') : '';
            $this->headers = [
                'Content-Type' => 'application/json-rpc;charset=utf-8',
                'Accept' => '*/*',
                'X-SBISSessionID' => $this->sessionId,
            ];

            $this->client->request('POST', self::SERVICE_URL, [
                'headers' => $this->headers,
                'body' => json_encode([
                    'jsonrpc' => '2.0',
                    'method' => 'СБИС.ИнформацияОТекущемПользователе',
                    'params' => ['Параметр' => []],
                    'id' => 0,
                ]),
            ]);
        } catch (Exception $e) {
            if ($e->getCode() !== 401) {
                throw $e;
            }
            $this->sessionId = $this->authorize()->result;
            cache()->set('sbis_key', $this->sessionId);
            $this->headers['X-SBISSessionID'] = $this->sessionId;
        }
    }

    public function name(): string
    {
        return 'sbis';
    }

    /**
     * Отправляет УПД/УПД-2 в СБИС через метод "СБИС.ЗаписатьДокумент".
     *
     * @throws EdoException при сбое сети или ошибке СБИС
     */
    public function sendUpd(string $xml): EdoMessageDto
    {
        $fileId = (string)(simplexml_load_string($xml))->attributes()['ИдФайл'];
        $documentId = (string)Str::uuid();
        $attachmentId = (string)Str::uuid();

        try {
            $response = $this->client->request('POST', self::SERVICE_URL, [
                'headers' => $this->headers,
                'body' => json_encode([
                    'jsonrpc' => '2.0',
                    'method' => 'СБИС.ЗаписатьДокумент',
                    'params' => [
                        'Документ' => [
                            'Идентификатор' => $documentId,
                            'Регламент' => [
                                'Название' => 'Реализация',
                            ],
                            'Вложение' => [
                                [
                                    'Идентификатор' => $attachmentId,
                                    'Файл' => [
                                        'ДвоичныеДанные' => base64_encode($xml),
                                        'Имя' => $fileId . '.xml',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'id' => 0,
                ]),
            ]);
        } catch (ServerException $e) {
            throw new EdoException(
                'СБИС вернул ошибку: ' . $e->getResponse()->getBody()->getContents(),
                $e->getCode(),
                $e
            );
        } catch (GuzzleException $e) {
            throw new EdoException('Сетевая ошибка при отправке УПД в СБИС: ' . $e->getMessage(), 0, $e);
        }

        $body = json_decode($response->getBody()->getContents());
        $providerStatus = $body->result->Регламент->Этап ?? null;
        $sbisDocumentId = $body->result->Идентификатор ?? $documentId;

        return new EdoMessageDto(
            (string)$sbisDocumentId,
            'sent',
            $providerStatus,
            [],
            Carbon::now()
        );
    }

    public function getStatus(string $messageId): EdoMessageDto
    {
        throw new EdoException('SbisProvider::getStatus ещё не реализован');
    }

    public function getIncomingDocuments(Carbon $from, Carbon $to): Collection
    {
        throw new EdoException('SbisProvider::getIncomingDocuments ещё не реализован');
    }

    public function downloadIncoming(string $messageId): string
    {
        throw new EdoException('SbisProvider::downloadIncoming ещё не реализован');
    }

    /**
     * @throws GuzzleException
     */
    private function authorize()
    {
        $response = $this->client->request('POST', self::AUTH_URL, [
            'headers' => $this->headers,
            'body' => json_encode([
                'jsonrpc' => '2.0',
                'method' => 'СБИС.Аутентифицировать',
                'params' => [
                    'Параметр' => [
                        'Логин' => env('SBIS_USER'),
                        'Пароль' => env('SBIS_PASS'),
                    ],
                ],
                'id' => 0,
            ]),
        ]);

        return json_decode($response->getBody()->getContents());
    }
}
