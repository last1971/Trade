<?php


namespace App\Services;


use App\Exceptions\CompelException;
use GuzzleHttp\Client;

class CompelApiService
{
    /**
     * @param string $method
     * @param array|null $params
     * @return mixed
     * @throws CompelException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function method(string $method, array  $params = null)
    {
        $client = new Client;

        $request = [
            'id' => 1,
            'method' => $method,
            'params' => $params
        ];
        $res = $client->request(
            'POST',
            env('COMPEL_API_URL'),
            [
                'json'   => $request
            ]
        );

        $response = json_decode($res->getBody()->getContents());

        if ($response->error !== null) {
            throw new CompelException($request, $response, $response->error->message);
        }

        return $response;
    }

    /**
     * @param string $name
     * @return mixed
     * @throws CompelException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function searchByName(string $name)
    {
        $params = [
            'user_hash'     => env('COMPEL_API_HASH'),
            'query_string'  => $name . '*',
        ];
        return $this->method('search_item_ext', $params);
    }

}
