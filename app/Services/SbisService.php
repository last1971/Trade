<?php

namespace App\Services;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ServerException;
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

    public function xml2html($xml)
    {
        try {
            $response = $this->client->request(
                'POST',
                'https://online.sbis.ru/service/?srv=1',
                [
                    'headers' => $this->headers,
                    'body' => json_encode([
                        "jsonrpc" => "2.0",
                        "method" => "СБИС.СформироватьHTMLИзXML",
                        "params" => [
                            "Параметр" => [
                                "XML" => base64_encode($xml)
                            ],
                        ],
                        "id" => 0
                    ])
                ]
            );
            $json = json_decode($response->getBody()->getContents());
            return base64_decode($json->result->HTML);
        } catch (ServerException $e) {
            throw new Exception($e->getResponse()->getBody()->getContents());
        }
    }
}
