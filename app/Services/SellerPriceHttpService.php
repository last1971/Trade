<?php

namespace App\Services;

use App\Http\Requests\SellerPriceRequest;
use App\Interfaces\ISellerPriceable;
use App\Seller;
use GuzzleHttp\Client;

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
