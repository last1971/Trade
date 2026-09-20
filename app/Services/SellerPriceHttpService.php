<?php

namespace App\Services;

use App\Http\Requests\SellerPriceRequest;
use App\Interfaces\ISellerPriceable;
use App\Seller;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class SellerPriceHttpService implements ISellerPriceable
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => env('PRICING_URI')
        ]);
    }

    private function request(SellerPriceRequest $request): array
    {
        $response = $this->client->get(
            'price/trade',
            [
                'query' => [
                    'supplierAlias' => 'elcopro',
                    'suppliers' =>
                        $request->get('sellerIds') ?? [$request->get('sellerId')],

                    'search' => $request->get('search'),
                    'dbOnly' => 'false',
                    'withCache' => $request->get('isUpdate') ? 'false' : 'true',
                ],
            ]
        );
        return json_decode($response->getBody()->getContents(), true);
    }

    public function searchFromRequest(SellerPriceRequest $request): array
    {
        return $this->request($request);
    }

    public function setGoodId(string $sellerGoodId, string $goodId): void
    {
        $this->client->post(
            'good/good-id',
            [
                'query' => [
                    'supplierAlias' => 'elcopro',
                    'supplierGoodId' => $sellerGoodId,
                    'goodId' => $goodId,
                ],
            ],
        );
    }

    public function getSellers(): array
    {
        $response = $this->client->get('supplier/rate/elcopro');
        $apiSellers = [];
        foreach (json_decode($response->getBody()->getContents(), true) as $apiSeller) {
            $apiSellers[$apiSeller['id']] = $apiSeller['rate'];
        }
        $sellers = Seller::query()
            ->whereIn('WHEREISPOSTCODE', array_keys($apiSellers))
            ->select('WHEREISPOSTCODE as sellerId', 'NAMEPOST as name', 'IS_API as isApi')
            ->get()
            ->toArray();
        array_unshift(
            $sellers,
            [
                'sellerId' => 0,
                'name' => 'ЭлкоПро',
                'isApi' => 1,
            ],
        );
        return array_map(function ($item) use ($apiSellers) {
            $item['rate'] = $apiSellers[$item['sellerId']];
            return $item;
        }, $sellers);
    }

    public function getBlocked(): array
    {
        $response = $this->client->get('supplier/blocked/elcopro');
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Справочная карточка детали с mpn.cc (модуль mpn в pricing-nest).
     * Ошибки сервиса не гасим и не переводим: отдаём тело и код как есть —
     * 400 валидации, 502 недоступности, 503 с blockedUntil разбирает UI.
     *
     * @return array{status: int, body: array}
     */
    public function getMpnPart(string $q, ?string $manufacturer = null, bool $refresh = false): array
    {
        try {
            $response = $this->client->get(
                'mpn/part',
                [
                    'query' => array_filter([
                        'q' => $q,
                        'manufacturer' => $manufacturer,
                        'refresh' => $refresh ? '1' : null,
                    ]),
                    'http_errors' => false,
                ]
            );
            return [
                'status' => $response->getStatusCode(),
                'body' => json_decode($response->getBody()->getContents(), true) ?? [],
            ];
        } catch (GuzzleException | \InvalidArgumentException $e) {
            // Сервис цен не отвечает или PRICING_URI на узле не задан (магазин) —
            // для диалога это одно и то же: справочник недоступен, но не 500.
            return [
                'status' => 502,
                'body' => ['error' => 'mpn_upstream', 'message' => $e->getMessage()],
            ];
        }
    }

    public function getRawResponse(array $ids): array
    {
        $response = $this->client->post(
            'good/raw-response',
            [
                'json' => [
                    'ids' => $ids
                ],
            ]
        );
        return json_decode($response->getBody()->getContents(), true);
    }
}
