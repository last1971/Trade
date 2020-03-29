<?php

namespace App\Services;

use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ServerException;
use Illuminate\Support\Str;
use Psr\SimpleCache\InvalidArgumentException;


class SbisService
{
    /**
     * @var Client
     */
    protected $client;
    /**
     * @var string
     */
    protected $sbisKey;
    /**
     * @var array
     */
    protected $headers;

    /**
     * SbisService constructor.
     * @throws GuzzleException
     * @throws InvalidArgumentException
     */

    public function __construct()
    {
        try {
            $this->client = new Client();
            $this->sbisKey = cache()->has('sbis_key') ? cache()->get('sbis_key') : '';
            $this->headers = [
                'Content-Type' => 'application/json-rpc;charset=utf-8',
                'Accept' => '*/*',
                'X-SBISSessionID' => $this->sbisKey
            ];

            $this->client->request(
                'POST',
                'https://online.sbis.ru/service/?srv=1',
                [
                    'headers' => $this->headers,
                    'body' => json_encode([
                        "jsonrpc" => "2.0",
                        "method" => "СБИС.ИнформацияОТекущемПользователе",
                        "params" => [
                            "Параметр" => []
                        ],
                        "id" => 0
                    ])
                ]
            );
        } catch (Exception $e) {
            if ($e->getCode() !== 401) throw $e;
            $this->sbisKey = $this->authorize()->result;
            cache()->set('sbis_key', $this->sbisKey);
            $this->headers['X-SBISSessionID'] = $this->sbisKey;
        }
    }

    /**
     * @return mixed
     * @throws GuzzleException
     */
    private function authorize()
    {
        $response = $this->client->request(
            'POST',
            'https://online.sbis.ru/auth/service/',
            [
                'headers' => $this->headers,
                'body' => json_encode([
                    "jsonrpc" => "2.0",
                    "method" => "СБИС.Аутентифицировать",
                    "params" => [
                        "Параметр" => [
                            "Логин" => env('SBIS_USER'),
                            "Пароль" => env('SBIS_PASS')
                        ]
                    ],
                    "id" => 0
                ])
            ]
        );
        return json_decode($response->getBody()->getContents());
    }

    public function createOut(Carbon $date, string $buyerKey)
    {
        $tksh = new TkshLibrary();
        $numbers = $tksh->utdAt($date);
        if (count($numbers) === 0) return null;
        try {
            $response = $this->client->request(
                'POST',
                'https://online.sbis.ru/service/?srv=1',
                [
                    'headers' => $this->headers,
                    'body' => json_encode([
                        "jsonrpc" => "2.0",
                        "method" => "СБИС.ЗаписатьДокумент",
                        "params" => [
                            "Документ" => [
                                "Идентификатор" => Str::uuid(),
                                // "Тип" => "ФактураИсх",
                                "Регламент" => [
                                    // "Идентификатор" => Str::uuid(),
                                    "Название" => "Реализация",
                                ],
                                "Вложение" => array_map(function ($number) use ($tksh) {
                                    return $tksh->utdToBase64($number);
                                }, $numbers)
                            ],
                        ],
                        "id" => 0
                    ])
                ]
            );
            return json_decode($response->getBody()->getContents());
        } catch (ServerException $e) {
            throw new Exception($e->getResponse()->getBody()->getContents());
        }
    }

    /**
     * @param string $date
     * @param string $type
     * @return mixed
     * @throws Exception
     */
    public function getDocuments(string $date, string $type)
    {
        try {
            $response = $this->client->request(
                'POST',
                'https://online.sbis.ru/service/?srv=1',
                [
                    'headers' => $this->headers,
                    'body' => json_encode([
                        "jsonrpc" => "2.0",
                        "method" => "СБИС.СписокДокументов",
                        "params" => [
                            "Фильтр" => [
                                "Тип" => $type,
                                "ДатаС" => $date,
                                "ДатаПо" => $date,
                                "Навигация" => ["РазмерСтраницы" => "50"],
                            ],
                        ],
                        "id" => 0
                    ])
                ]
            );
            $json = json_decode($response->getBody()->getContents());
            return $json->result->Документ;
        } catch (ServerException $e) {
            throw new Exception($e->getResponse()->getBody()->getContents());
        }
    }

    public function getDataFromDocument($id)
    {
        $xml = $this->getFileFromDocument($id);
        $sellerINN = (string)$xml->Документ->СвСчФакт->СвПрод->ИдСв->СвЮЛУч['ИННЮЛ'];
        $fbs = new FirebirdService();
        $lines = collect();
        $counrties = array_flip(array_filter(config('country'), function ($val) {
            return $val;
        }));
        foreach ($xml->Документ->ТаблСчФакт->СведТов as $line) {
            $lines->push(collect([
                'name' => (string)$line['НаимТов'],
                'quantity' => (string)$line['КолТов'],
                'price' => round((float)$line['ЦенаТов'] * 1.2, 2),
                'sum' => (float)$line['СтТовУчНал'],
                'country' => $line->СвТД ? $counrties[(string)$line->СвТД['КодПроисх']] : '',
                'gtd' => $line->СвТД ? (string)$line->СвТД['НомерТД'] : ''
            ]));
        }
        return collect([
            'uuid' => $id,
            'number' => (string)$xml->Документ->СвСчФакт['НомерСчФ'],
            'date' => (string)$xml->Документ->СвСчФакт['ДатаСчФ'],
            'come' => (string)$xml->Документ->СвСчФакт['ДатаСчФ'],
            'seller' => $fbs->getSellerByINN($sellerINN),
            'lines' => $lines,
        ]);
    }

    public function getFileFromDocument($id)
    {
        $doc = $this->getDocument($id);
        $url = null;
        foreach ($doc->Вложение as $data) {
            if ($data->Тип === 'УпдСчфДоп') {
                $url = $data->Файл->Ссылка;
                break;
            }
        }
        return !$url ? $url : $this->getFile($url);
    }

    public function getDocument(string $id)
    {
        try {
            $response = $this->client->request(
                'POST',
                'https://online.sbis.ru/service/?srv=1',
                [
                    'headers' => $this->headers,
                    'body' => json_encode([
                        "jsonrpc" => "2.0",
                        "method" => "СБИС.ПрочитатьДокумент",
                        "params" => [
                            "Документ" => [
                                "Идентификатор" => $id,
                            ],
                        ],
                        "id" => 0
                    ])
                ]
            );
            $json = json_decode($response->getBody()->getContents());
            return $json->result;
        } catch (ServerException $e) {
            throw new Exception($e->getResponse()->getBody()->getContents());
        }
    }

    public function getFile($url)
    {
        try {
            $response = $this->client->request(
                'GET',
                $url,
                [
                    'headers' => $this->headers,
                ]
            );
            return simplexml_load_string($response->getBody()->getContents());
        } catch (ServerException $e) {
            throw new Exception($e->getResponse()->getBody()->getContents());
        }
    }
}
