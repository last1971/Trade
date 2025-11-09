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
            'password'      => env('PROM_PASS'), // Пароль уже в виде md5 хеша в .env
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

    /**
     * Получение списка счетов (заказов)
     * @param array $params
     * @return mixed
     * @throws
     */
    public function getOrders(array $params = [])
    {
        return $this->method('bills_data_get', $params);
    }

    /**
     * Создание счета
     * @param array $params
     * @return mixed
     * @throws
     */
    public function createBill(array $params = [])
    {
        return $this->method('bill_ins', $params);
    }

    /**
     * Получение позиций счета
     * @param int $billId
     * @return mixed
     * @throws
     */
    public function getBillItems(int $billId)
    {
        $params = ['bill_id' => $billId];
        return $this->method('bill_items_get', $params);
    }

    /**
     * Добавление позиции в счет
     * @param array $params
     * @return mixed
     * @throws
     */
    public function addBillItem(array $params)
    {
        return $this->method('bill_item_ins', $params);
    }

    /**
     * Изменение количества позиции счета
     * @param array $params
     * @return mixed
     * @throws
     */
    public function updateBillItemQuantity(array $params)
    {
        return $this->method('bill_item_quant', $params);
    }

    /**
     * Удаление позиции счета
     * @param array $params
     * @return mixed
     * @throws
     */
    public function deleteBillItem(array $params)
    {
        return $this->method('bill_item_del', $params);
    }
}
