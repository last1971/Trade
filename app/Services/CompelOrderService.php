<?php


namespace App\Services;

use App\Interfaces\ISellerOrderService;

class CompelOrderService implements ISellerOrderService
{
    /**
     * @var int
     */
    private $page = 1;

    /**
     * @var bool
     */
    private $index = false;

    /**
     * @var array
     */
    private array $filterValues;

    /**
     * @param $request
     * @return $this
     */
    public function index($request)
    {
        $this->page = $request->get('page') ?? 1;
        $filterAttributes = $request->get('filterAttributes');
        $this->filterValues = $request->get('filterValues');
        if (is_array($filterAttributes)) {
            $this->index = array_search('closed', $filterAttributes);
        }
        return $this;
    }

    /**
     * @param int $itemsPerPage
     * @return \Illuminate\Support\Collection
     * @throws \App\Exceptions\CompelException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function paginate(?int $itemsPerPage = 20)
    {
        $compel = new CompelApiService();
        $params = [];
        $current_page = $this->page;
        $params['page_num'] = $current_page;
        $per_page = $itemsPerPage ?? 20;
        $params['records_per_page'] = $per_page;
        
        // 1. Сортировка по дате заказа (desc)
        $params['order_sales_date'] = 'desc';
        
        // 2. Фильтр по статусу - только открытые заказы (backorder)
        $params['status_filter'] = 'backorder';
        
        $response = $compel->orders($params);
        
        $data = collect((array)$response->result->sales)
            // 3. Дополнительная фильтрация:
            // - только открытые заказы
            // - НЕТ подобранных линий (picked_lines = false)
            // - НЕТ DMS (has_dms = false)
            ->filter(fn($order) => 
                $order->sales_status === 'Открытый заказ' && 
                $order->picked_lines === false &&
                $order->has_dms === false
            )
            ->map(fn($order) => [
                'id' => $order->sales_id,
                'seller_id' => config('pricing.Compel.sellerId'),
                'date' => $order->sales_date,
                // 4. Номер заказа = sales_id
                'number' => $order->sales_id,
                'remark' => $order->comment,
                'amount' => $order->sales_amount > 0 ? $order->sales_amount : $order->amount,
                'closed' => false, // Все заказы открытые после фильтрации
                'document_number' => !empty($order->document_number) ? $order->document_number : null,
                'date_deadline' => !empty($order->date_deadline) ? $order->date_deadline : null,
            ])
            ->values(); // Переиндексация после filter
        $total = $response->result->records;
        $last_page = $response->result->pages;
        $from = ($current_page - 1) * $per_page + 1;
        $to = $current_page * $per_page;
        $to = $to > $total ? $total : $to;
        return collect(
            compact('current_page', 'data', 'from', 'last_page', 'per_page', 'to', 'total')
        );
    }

    /**
     * Создание счёта в Compel
     * @param $request
     * @return array
     * @throws \App\Exceptions\CompelException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function create($request)
    {
        $compel = new CompelApiService();
        
        $data = is_array($request) ? $request : $request->item;
        
        $params = [];
        
        // Примечание к заказу (опционально)
        if (!empty($data['comment'])) {
            $params['comment'] = $data['comment'];
        }
        
        // Флаг резервирования (по умолчанию true)
        $params['reserve_sale'] = isset($data['reserve_sale']) ? (bool)$data['reserve_sale'] : true;
        
        $params['auto_credit'] = true;
        
        $response = $compel->createOrder($params);
        
        // Compel возвращает sales_id и date_dead_line
        $result = $response->result ?? null;
        
        if (!$result || !isset($result->sales_id)) {
            throw new \Exception('Invalid response from Compel API');
        }
        
        $salesId = $result->sales_id;
        
        return [
            'id' => $salesId,
            'seller_id' => config('pricing.Compel.sellerId'),
            'date' => now()->toDateString(),
            'number' => $salesId,
            'remark' => $data['comment'] ?? '',
            'amount' => 0,
            'closed' => false,
        ];
    }

    /**
     * Добавление строк в заказ Compel
     * @param string $salesId - ID заказа
     * @param array $lines - массив строк для добавления
     * @param bool $reserveAll - резервировать все строки (по умолчанию true)
     * @return mixed
     * @throws \App\Exceptions\CompelException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function addLines(string $salesId, array $lines, bool $reserveAll = true)
    {
        $compel = new CompelApiService();
        
        // Формируем sales_lines в формате Compel API
        $salesLines = [];
        foreach ($lines as $line) {
            $isReserv = $line['is_reserv'] ?? $reserveAll;
            
            $salesLine = [
                'item_id' => (string)$line['item_id'],
                'qty' => (int)$line['qty'],
                'is_reserv' => (bool)$isReserv,
            ];
            
            // process_qty нужен только если резервируем
            if ($isReserv) {
                $salesLine['process_qty'] = (int)$line['qty'];
            }
            
            // Заглушка для области применения (обязательное поле)
            if (!empty($line['application_area_id'])) {
                $salesLine['application_area_id'] = (string)$line['application_area_id'];
            } else {
                $salesLine['application_area_id'] = '021';
            }
            
            // Дополнительные поля для ДМС (опционально)
            if (!empty($line['item_name'])) {
                $salesLine['item_name'] = $line['item_name'];
            }
            if (!empty($line['invc_brend_alias'])) {
                $salesLine['invc_brend_alias'] = $line['invc_brend_alias'];
            }
            if (!empty($line['prognosis_id'])) {
                $salesLine['prognosis_id'] = $line['prognosis_id'];
            }
            if (!empty($line['min_qty'])) {
                $salesLine['min_qty'] = $line['min_qty'];
            }
            if (!empty($line['max_qty'])) {
                $salesLine['max_qty'] = $line['max_qty'];
            }
            if (!empty($line['qty_multiples'])) {
                $salesLine['qty_multiples'] = $line['qty_multiples'];
            }
            
            $salesLines[] = $salesLine;
        }
        
        $params = [
            'sales_id' => $salesId,
            'sales_lines' => $salesLines,
        ];
        
        $response = $compel->addOrderLines($params);
        
        // API возвращает только статус, не возвращает добавленные строки
        return $response->result ?? null;
    }

    /**
     * Получение строк заказа Compel
     * @param string $salesId - ID заказа
     * @return array
     * @throws \App\Exceptions\CompelException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getLines(string $salesId)
    {
        $compel = new CompelApiService();
        
        $response = $compel->getOrderLines($salesId);
        
        $lines = collect((array)($response->result->sales_lines ?? []))
            ->map(function($lineWrapper) {
                // Compel API оборачивает каждую строку в stdClass
                $line = is_object($lineWrapper) && property_exists($lineWrapper, 'stdClass') 
                    ? $lineWrapper->stdClass 
                    : $lineWrapper;
                
                $currencyCode = $line->currency_code ?? 'USD';
                // Compel возвращает RUR вместо RUB
                if ($currencyCode === 'RUR') {
                    $currencyCode = 'RUB';
                }
                
                return [
                    'line_id' => $line->line_id ?? null,
                    'item_id' => $line->item_id ?? null,
                    'item_name' => $line->item_name ?? '',
                    'brend' => $line->brend ?? '',
                    'package_name' => $line->package_name ?? '',
                    'sales_qty' => (int)($line->sales_qty ?? 0),
                    'price' => (float)($line->price ?? 0),
                    'amount' => (float)($line->amount ?? 0),
                    'currency_code' => $currencyCode,
                    'reserve_qty' => (int)($line->reserve_qty ?? 0),
                    'reservation_end' => $line->reservation_end ?? null,
                ];
            })
            ->values()
            ->toArray();
        
        return [
            'lines' => $lines,
            'total' => count($lines),
        ];
    }

    /**
     * Получение информации о заказе Compel
     * @param string $salesId - ID заказа
     * @return array
     * @throws \App\Exceptions\CompelException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getOrder(string $salesId)
    {
        $compel = new CompelApiService();
        
        $params = [
            'sales_id' => $salesId,
        ];
        
        $response = $compel->orders($params);
        
        // Ищем заказ с нужным ID
        $order = collect((array)($response->result->sales ?? []))
            ->firstWhere('sales_id', $salesId);
        
        if (!$order) {
            throw new \Exception('Order not found');
        }
        
        return [
            'id' => $order->sales_id,
            'seller_id' => config('pricing.Compel.sellerId'),
            'date' => $order->sales_date,
            'number' => $order->sales_id,
            'remark' => $order->comment ?? '',
            'amount' => $order->sales_amount > 0 ? $order->sales_amount : ($order->amount ?? 0),
            'closed' => $order->sales_status !== 'Открытый заказ',
        ];
    }

    /**
     * Изменение количества в строке заказа Compel
     * @param string $salesId - ID заказа
     * @param string $lineId - ID строки
     * @param int $quantity - новое количество
     * @return mixed
     * @throws \App\Exceptions\CompelException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function updateLineQuantity(string $salesId, string $lineId, int $quantity)
    {
        $compel = new CompelApiService();
        
        $salesLines = [[
            'line_id' => $lineId,
            'qty' => $quantity,
            'process_qty' => $quantity,
            'is_reserv' => true,
        ]];
        
        $params = [
            'sales_id' => $salesId,
            'sales_lines' => $salesLines,
        ];
        
        $response = $compel->editOrderLines($params);
        
        return $response->result ?? null;
    }

    /**
     * Удаление строки заказа Compel
     * @param string $salesId - ID заказа
     * @param string $lineId - ID строки
     * @return mixed
     * @throws \App\Exceptions\CompelException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function deleteLine(string $salesId, string $lineId)
    {
        $compel = new CompelApiService();
        
        $salesLines = [[
            'line_id' => $lineId,
            'close' => true,
        ]];
        
        $params = [
            'sales_id' => $salesId,
            'sales_lines' => $salesLines,
        ];
        
        $response = $compel->editOrderLines($params);
        
        return $response->result ?? null;
    }

    /**
     * Создание и отправка счета
     * @param string $salesId - ID заказа
     * @return mixed
     * @throws \App\Exceptions\CompelException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendInvoice(string $salesId)
    {
        $compel = new CompelApiService();
        
        $response = $compel->createInvoice($salesId);
        
        return $response->result ?? null;
    }

    /**
     * Отгрузка заказа
     * @param string $salesId - ID заказа
     * @param string|null $customerDeliveryTypeId - ID способа доставки
     * @param string|null $dateDeadline - дата действия (формат YYYY-MM-DD)
     * @return mixed
     * @throws \Exception
     * @throws \App\Exceptions\CompelException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function shipOrder(string $salesId, ?string $customerDeliveryTypeId, ?string $dateDeadline)
    {
        if (!$customerDeliveryTypeId || !$dateDeadline) {
            throw new \Exception('customer_delivery_type_id and date_deadline are required for Compel');
        }
        
        $compel = new CompelApiService();
        
        $response = $compel->shipOrder($salesId, $customerDeliveryTypeId, $dateDeadline);
        
        return $response->result ?? null;
    }
}
