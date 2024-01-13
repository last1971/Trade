<?php

namespace App\Services;

use GuzzleHttp\Client;

class MarketplaceService
{
    private Client $client;
    public function getCoefficients(): void
    {
        $this->client = new Client([
            'base_uri' => env('OZON_URI', 'http://localhost:3002')
        ]);

    }
}
