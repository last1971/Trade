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
    private function method(string $method, array  $params = [])
    {
        $client = new Client;

        $queryParams = array_merge($params, ['user_hash' => env('COMPEL_API_HASH')]);

        $request = [
            'id' => 1,
            'method' => $method,
            'params' => $queryParams
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
        $params = ['query_string'  => $name . '*'];
        return $this->method('search_item_ext', $params);
    }

    /**
     * @param array $params
     * @return mixed
     * @throws CompelException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function orders(array $params = [])
    {
        $queryParams = array_merge(['order_sales_id' => 'desc'], $params);
        return $this->method('sales_info', $queryParams);
    }

    /**
     * @param string $name
     * @return mixed
     * @throws CompelException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function searchInCenter(string $name)
    {
        $params = [
            'query_string'  => $name . '*',
            'calc_price' => true,
            'calc_qty' => true,
        ];
        return $this->method('search_item_name_h', $params);
    }

    /**
     * Создание счёта (заказа) в Compel
     * @param array $params
     * @return mixed
     * @throws CompelException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function createOrder(array $params = [])
    {
        // Параметры:
        // - comment: примечание к заказу
        // - reserve_sale: флаг резервирования всех позиций
        // - sales_lines: массив строк заказа
        return $this->method('make_order_ext', $params);
    }

    /**
     * Добавление строк в существующий заказ
     * @param array $params
     * @return mixed
     * @throws CompelException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function addOrderLines(array $params = [])
    {
        // Параметры:
        // - sales_id: код заказа (обязательный)
        // - is_reserv_all: резервировать все строки
        // - sales_lines: массив строк для добавления
        return $this->method('sales_handle_add_ext', $params);
    }

    /**
     * Получение строк заказа
     * @param string $salesId
     * @return mixed
     * @throws CompelException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getOrderLines(string $salesId)
    {
        $params = [
            'sales_id' => $salesId,
            'skip_pagination' => true,
            'show_reserve_qty' => true,
        ];
        return $this->method('sales_lines', $params);
    }

    /**
     * Редактирование строк заказа
     * @param array $params
     * @return mixed
     * @throws CompelException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function editOrderLines(array $params = [])
    {
        // Параметры:
        // - sales_id: ID заказа
        // - sales_lines: массив строк для редактирования
        return $this->method('sales_handle_edit_ext', $params);
    }

    /**
     * Создание и отправка счета
     * @param string $salesId
     * @return mixed
     * @throws CompelException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function createInvoice(string $salesId)
    {
        $params = [
            'sales_id' => $salesId,
            'send_by_email' => true,
        ];
        return $this->method('create_invoice', $params);
    }

    /**
     * Получение методов отгрузки
     * @return mixed
     * @throws CompelException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getDeliveryModes()
    {
        return $this->method('get_dlv_mode', []);
    }

    /**
     * Отгрузка заказа
     * @param string $salesId
     * @param string $customerDeliveryTypeId
     * @param string $dateDeadline
     * @return mixed
     * @throws CompelException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function shipOrder(string $salesId, string $customerDeliveryTypeId, string $dateDeadline)
    {
        $params = [
            'sales_id' => $salesId,
            'is_pick_all' => true,
            'customer_delivery_type_id' => $customerDeliveryTypeId,
            'date_dead_line' => $dateDeadline,
        ];
        return $this->method('sales_handle_edit_ext', $params);
    }

}
