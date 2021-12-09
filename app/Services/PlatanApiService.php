<?php

namespace App\Services;

use GuzzleHttp\Client;
use stdClass;


class PlatanApiService
{
    private Client $client;

    public function __construct() {
        $this->client = new Client([
            'base_uri' => env('PLATAN_URI', 'http://www.platan.ru/shop/export/price.json')
        ]);
    }

    public function searchByName(string $search): stdClass
    {
        $params = [
            'query' => [ 'search' => $search ],
        ];
        $response = $this->client->get('', $params);
        return json_decode($response->getBody()->getContents());
    }

    public function searchById(array $ids): stdClass
    {
        $params = '?login=' . env('PLATAN_LOGIN') . '&id=' . implode('&id=', $ids) . '&sha1=' .
            strtoupper(
                sha1(env('PLATAN_LOGIN') . ';' . implode(';', $ids) . ';' . env('PLATAN_PASS'))
            );
        $response = $this->client->get($params);
        return json_decode($response->getBody()->getContents());
    }
}
