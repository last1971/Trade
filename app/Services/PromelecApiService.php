<?php


namespace App\Services;


use Exception;
use GuzzleHttp\Client;

class PromelecApiService
{
    /**
     * @param $method string
     * @param $params array
     * @return \stdClass
     * @throws
     */

    public function method(string $method, array  $params = null)
    {
        $client = new Client;

        $request = [
            'login'         => env('PROM_LOGIN'),
            'password'      => md5(env('PROM_PASS')),
            'customer_id'   => env('PROM_ID'),
            'method'        => $method,
        ];
        $request = $params ? array_merge($params, $request) : $request;
        $res = $client->request(
            'POST',
            env('PROM_API_URL'),
            [
                'json'   => $request
            ]
        );

        $response = json_decode($res->getBody()->getContents());

        if (isset($response->error)) {
            throw new Exception($response->error);
        }

        return $response;
    }

    /**
     * @param string $name
     * @return mixed
     * @throws
     */

    public function searchByName(string $name)
    {
        $params = [
            'name'      => $name,
            'extended'  => 1
        ];
        return $this->method('items_data_find', $params);
    }

    public function getVendorComment()
    {
        return $this->method('vendor_comment_get');
    }
}
