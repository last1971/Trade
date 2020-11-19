<?php


namespace App\Services;

use App\Buyer;
use App\Exceptions\ApiException;
use App\User;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Str;

// use GuzzleHttp\Psr7\Request;

class AtolService
{
    private Client $client;
    private $connected = false;

    public $uri = '';
    public User $operator;
    public $status = false;
    public $error = 1;

    /**
     * AtolService constructor.
     * @throws \Throwable
     */
    function __construct()
    {
        $this->uri = env('ATOL_URI', '');
        throw_if($this->uri === '', new \Exception('Не указаны данные кассы'));
        $this->client = new Client([
            'base_uri' => $this->uri,
            'timeout' => 600.0,
            'headers' => ['Content-Type' => 'application/json'],
        ]);
    }

    /** Reading data from cashier
     * @param string $uri
     * @param array $data
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function getData(string $uri = '', $data = array())
    {
        $response = $this->client->request('GET', $uri, $data);
        $body = $response->getBody();
        return json_decode($body, true);
    }

    /** Writing data to the cashier
     * @param array $data
     * @param string $uri
     * @return mixed
     * @throws GuzzleException
     */
    private function postData(array $data = array(), string $uri = '/api/v2/requests')
    {
        $response = $this->client->request('POST', $uri, ['json' => $data]);
        $body = $response->getBody();
        return json_decode($body, true);
    }

    /** Waiting for the end of the task with id = $uuid
     * @param string $uuid
     * @return mixed|null
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Throwable
     */
    private function checkStatus(string $uuid)
    {
        $not_ready = true;
        $this->error = 1;
        $counter = 0;
        $response = null;
        while ($not_ready) {
            $response = $this->getData('/requests/' . $uuid);
            if ($response['results'][0]['status'] === 'error') {
                throw(new ApiException(
                    $response['results'][0]['errorDescription']
                ));
            }
            $not_ready = ($response['results'][0]['status'] !== 'ready');
            sleep(1);
            $counter++;
            if ($counter > 30) {
                break;
            } //ждем 30 секунд и типа отваливаемся по таймауту
        }
        throw_if(
            $not_ready,
            new \Exception('Касса не ответила в течении 30 секунд для UUID=' . $uuid)
        );
        $this->status = true;
        $this->error = $response['results'][0]['errorCode'] ?? 1;
        return $response;
    }

    /** Connect to cashier
     * @return bool
     * @throws GuzzleException
     * @throws \Throwable
     */

    public function connect(): bool
    {
        if (!$this->connected)
            try {
                $this->connected = true;
                $this->checkDeviceStatus();
            } catch (GuzzleException $e) {
                $this->connected = false;
                if ($e->getCode() !== 403) throw $e;
            }
       return $this->connected;
        //$response = $this->getData('/stat/requests');
        //$this->connected = (is_array($response) and array_key_exists('ready_count', $response));
    }

    /** Activate cashier
     * @return bool
     */
    public function activateDevice(): bool
    {
        try {
            $this->client->request('POST', '/api/v2/activateDevice');
            $this->connected = true;
        } catch (GuzzleException $e) {
            $this->connected = false;
        }
        return $this->connected;
    }

    /** Deactivate cashier
     * @return bool
     */
    public function deactivateDevice(): bool
    {
        if ($this->connected)
            try {
                $this->client->request('POST', '/api/v2/deactivateDevice');
                $this->connected = false;
            } catch (GuzzleException $e) {
                $this->connected = true;
            }
            return $this->connected;
    }

    /**
     * @return mixed
     * @throws GuzzleException
     * @throws \Throwable
     */
    function checkTaskQueue()
    {
        throw_if(!$this->connected, new \Exception('Касса не подключена'));
        return $this->getData('/api/v2/getRequestsQueueStatus');
    }

    /**
     * @return mixed
     * @throws GuzzleException
     * @throws \Throwable
     */
    function checkDeviceStatus()
    {
        throw_if(!$this->connected, new \Exception('Касса не подключена'));
        return $this->postData(array(),'/api/v2/operations/queryDeviceStatus');
    }

    /**
     * @return bool
     * @throws GuzzleException
     * @throws \Throwable
     */
    public function isShiftOpen()
    {
        $response = $this->postData(array(), '/api/v2/operations/queryShiftStatus');
        throw_if(
            empty($response['shiftStatus']) || empty($response['shiftStatus']['state']),
            new \Exception('Не известно открыта ли смена')
        );
        return $response['shiftStatus']['state'] === 'opened';
    }

    /**
     * @return mixed|null
     * @throws GuzzleException
     * @throws \Throwable
     */
    function openShift()
    {
        throw_if(!$this->connected, new \Exception('Касса не подключена'));
        throw_if($this->isShiftOpen(), new \Exception('Смена уже открыта!'));
        throw_if(
            !$this->operator || !$this->operator->employee || !$this->operator->employee->INN,
            new \Exception('Не известный кассир')
        );
        $newId = Str::uuid();
        $task = array(
            'uuid' => $newId,
            'request' => array(
                'type' => 'openShift',
                'operator' => array(
                    'name' => $this->operator->employee->FULLNAME,
                    'vatin' => $this->operator->employee->INN
                )
            )
        );
        $this->postData($task);
        return $this->checkStatus($newId);
    }

    /**
     * @return mixed|null
     * @throws GuzzleException
     * @throws \Throwable
     */
    function closeShift()
    {
        throw_if(!$this->connected, new \Exception('Касса не подключена'));
        throw_if(!$this->isShiftOpen(), new \Exception('Смена уже закрыта!'));
        throw_if(
            !$this->operator || !$this->operator->employee || !$this->operator->employee->INN,
            new \Exception('Не известный кассир')
        );
        $newId = Str::uuid();
        $task = array(
            'uuid' => $newId,
            'request' => array(
                'type' => 'closeShift',
                'operator' => array(
                    'name' => $this->operator->employee->FULLNAME,
                    'vatin' => $this->operator->employee->INN
                )
            )
        );
        $this->postData($task);
        return $this->checkStatus($newId);
    }

    /**
     * @param array $productList
     * @param string $paymentMethod
     * @param int $department
     * @return array[]
     */
    private function createItems(array $productList, string $paymentMethod = 'fullPayment', int $department = 1)
    {
        return array_map(function ($item) use ($paymentMethod, $department){
            // needs to be finalized taking into account several tax rates
            $tax = [
                'type' => env('TAX_TYPE') ?? 'none'
            ];
            if ($tax['type'] !== 'none') {
                $tax['sum'] = !empty($item[$tax['type']]) ? $item[$tax['type']] : $item['amount'] / 120 * 20;
            }
            return [
                'type' => 'position',
                'name' => $item['name'],
                'price' => floatval($item['price']),
                'quantity' => $item['quantity'],
                'amount' => floatval($item['amount']),
                'paymentMethod' => $paymentMethod,
                'paymentObject' => 'commodity',
                'department' => $department,
                'measurementUnit' => !empty($item['unit']) ? $item['unit'] : 'шт',
                'tax' => $tax,
            ];
        }, $productList);
    }

    /**
     * @param array $productList
     * @param string $type
     * @param string $paymentType
     * @param Buyer|null $buyer
     * @param string $paymentMethod
     * @param int $department
     * @param bool $electronically
     * @return mixed|null
     * @throws GuzzleException
     * @throws \Throwable
     */
    function receipt(
        array $productList,
        string $type = 'sell',
        string $paymentType = 'cash',
        Buyer $buyer = null,
        string $paymentMethod = 'fullPayment',
        int $department = 1,
        bool $electronically = false
    )
    {
        throw_if(
            !$this->operator || !$this->operator->employee || !$this->operator->employee->INN,
            new \Exception('Не известный кассир')
        );

        if (!$this->isShiftOpen()) {
            $this->openShift();
        }

        $total = array_reduce($productList, function ($acc, $item) {
            return $acc + floatval($item['amount']);
        }, 0);

        $request = [
            'type' => $type,
            'electronically' => $electronically,
            'taxationType' => env('TAXATION_TYPE') ?? 'osn',
            'operator' => [
                'name' => $this->operator->employee->FULLNAME,
                'vatin' => $this->operator->employee->INN
            ],
            'items' => $this->createItems($productList, $paymentMethod, $department),
            'payments' => [
                [
                    'type' => $paymentType,
                    'sum' => $total,
                ]
            ],
            'total' => $total,
        ];

        if ($buyer) {
            $request['clientInfo'] = ['emailOrPhone' => $buyer->EMAIL];
        }

        $newId = Str::uuid();
        $task = array('uuid' => $newId, 'request' => $request);
        $this->postData($task);
        return $this->checkStatus($newId);
    }
}
