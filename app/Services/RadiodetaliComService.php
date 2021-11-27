<?php

namespace App\Services;

use GuzzleHttp\Client;

class RadiodetaliComService
{
    private $ignoredStores = '10,12,13,14,15';

    public function search(string $name): array
    {
        $client = new Client();

        $response = $client->get(
            env('RADIODETALI_COM_URI'),
            [
                'query' => [
                    'id' => env('RADIODETALI_COM_TOKEN'),
                    'offs' => $this->ignoredStores,
                    'opt' => 'by_part_n',
                    'seek' => $name,
                 ]
            ]
        );
        $res = json_decode($response->getBody()->getContents());
        return empty($res->item) ? [] : $res->item;
    }
}
