<?php

namespace App\Services\Pricing;

use GuzzleHttp\Client;
use Illuminate\Support\Collection;

class Electronica
{
    /**
     * @param string $search
     * @return Collection
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function __invoke(string $search): Collection
    {
        $client = new Client();

        $headers = [
            'Authorization' => 'Bearer ' . env('ELECTRONICA_TOKEN', '123'),
            'Accept'        => 'application/json',
        ];

        $response = $client->get('https://electronica.su/api/seller-price', [
            'headers' => $headers,
            'query' => ['sellerId' => 0, 'search' => $search],
        ]);

        return collect();
    }

}
