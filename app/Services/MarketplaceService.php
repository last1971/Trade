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

    /**
     * Список SKU магазина на маркетплейсе из Nest-сервиса (живёт в его памяти,
     * отвечает мгновенно). SKU — артикулы вида "498824" и "498824-1000"
     * (вариант кратности), GOODSCODE = числовой префикс.
     *
     * @param string $service ozon|wb|yandex|avito (enum в нижнем регистре)
     * @return string[]
     */
    public function skuList(string $service): array
    {
        $client = new Client([
            'base_uri' => env('OZON_URI', 'http://localhost:3002'),
            'timeout' => 30,
        ]);
        $response = $client->get("/api/good/sku-list/{$service}");
        return json_decode($response->getBody()->getContents(), true) ?: [];
    }
}
