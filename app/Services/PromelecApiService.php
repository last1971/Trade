<?php


namespace App\Services;


use Exception;
use GuzzleHttp\Client;

class PromelecApiService
{
    /**
     * Получение customer_id на основе фирм авторизованного пользователя
     * @return int
     */
    public function getCustomerId(): int
    {
        $customerIds = config('pricing.Promelec.customerIds', [
            38 => 88053,
            31 => 71695,
        ]);

        // Получаем авторизованного пользователя
        $user = request()->user();

        // Если пользователь не авторизован или нет фирм, возвращаем значение по умолчанию
        if (!$user || !$user->userFirms || $user->userFirms->isEmpty()) {
            return $customerIds[38];
        }

        // Получаем список FIRM_ID пользователя
        $userFirmIds = $user->userFirms->pluck('firm.FIRM_ID')->filter()->toArray();

        // Проверяем наличие firm_id в порядке приоритета
        foreach ($customerIds as $firmId => $customerId) {
            if (in_array($firmId, $userFirmIds)) {
                return $customerId;
            }
        }

        // Если ничего не найдено, возвращаем значение по умолчанию
        return $customerIds[38];
    }

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
            'customer_id'   => $this->getCustomerId(),
            'method'        => $method,
        ];
        $request = $params ? array_merge($request, $params) : $request;
        
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
     * Получение PDF документа счета
     * @param int $billId
     * @return string - содержимое PDF файла
     * @throws
     */
    public function getBillDocument(int $billId)
    {
        $client = new Client;

        $request = [
            'login'         => env('PROM_LOGIN'),
            'password'      => env('PROM_PASS'),
            'customer_id'   => $this->getCustomerId(),
            'method'        => 'bill_document_get',
            'bill_id'       => $billId,
        ];
        
        $res = $client->request(
            'POST',
            env('PROM_API_URL'),
            [
                'json'   => $request
            ]
        );

        return $res->getBody()->getContents();
    }

    /**
     * Установка статуса счета (отгрузка)
     * @param int $billId
     * @param int $status
     * @return mixed
     * @throws
     */
    public function setBillStatus(int $billId, int $status = 2)
    {
        $params = [
            'bill_id' => $billId,
            'status' => $status,
        ];
        return $this->method('bill_Items_Status_Set', $params);
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
