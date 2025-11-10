<?php

namespace App\Services;

use App\Interfaces\ISellerOrderService;

class PromelecOrderService implements ISellerOrderService
{
    protected $page = 1;

    /**
     * Установка текущей страницы
     * @param int $page
     * @return $this
     */
    public function page(int $page)
    {
        $this->page = $page;
        return $this;
    }

    /**
     * Конвертация даты из формата DD.MM.YYYY в YYYY-MM-DD
     * @param string $date
     * @return string
     */
    private function convertDate(string $date)
    {
        try {
            $dt = \DateTime::createFromFormat('d.m.Y', $date);
            return $dt ? $dt->format('Y-m-d') : $date;
        } catch (\Exception $e) {
            return $date;
        }
    }

    /**
     * Преобразование строки счета Promelec к формату приложения
     * @param object|array $item
     * @return array
     */
    private function formatBillItem($item): array
    {
        if (is_array($item)) {
            $item = (object)$item;
        }

        $quantity = (int)($item->quant ?? 0);
        $price = (float)($item->price ?? $item->price_main ?? 0);
        $amount = (float)($item->sum_ ?? ($price * $quantity));
        $reservationEnd = $item->date_service_1 ?? null;

        return [
            'line_id' => $item->id ?? null,
            'item_id' => $item->item_id ?? null,
            'item_name' => $item->name ?? '',
            'brend' => '',
            'package_name' => '',
            'sales_qty' => $quantity,
            'price' => $price,
            'amount' => $amount,
            'currency_code' => 'RUB',
            'reserve_qty' => $quantity,
            'reservation_end' => $reservationEnd ? $this->convertDate($reservationEnd) : null,
            'status' => $item->statusname ?? '',
            'status_code' => $item->status ?? null,
            'vendor_id' => $item->vendor_id ?? null,
            'order_id' => $item->order_id ?? null,
            'order_item_id' => $item->order_item_id ?? null,
        ];
    }

    /**
     * Преобразование данных счета Promelec к формату приложения
     * @param object|array $bill
     * @return array
     */
    private function formatBill($bill): array
    {
        if (is_array($bill)) {
            $bill = (object)$bill;
        }

        return [
            'id' => $bill->bill_id ?? null,
            'seller_id' => config('pricing.Promelec.sellerId'),
            'date' => isset($bill->date_) ? $this->convertDate($bill->date_) : null,
            'number' => $bill->number ?? ($bill->bill_id ?? null),
            'remark' => $bill->client_comment ?? '',
            'amount' => $bill->sum_ ?? 0,
            'closed' => false,
            'document_number' => null,
            'date_deadline' => null,
        ];
    }

    /**
     * Получение списка заказов с пагинацией (для контроллера)
     * @param $request
     * @return $this
     */
    public function index($request)
    {
        $this->page = $request->get('page', 1);
        return $this;
    }

    /**
     * Получение списка счетов (заказов) Promelec с пагинацией
     * @param int|null $itemsPerPage
     * @return array
     * @throws \Exception
     */
    public function paginate(?int $itemsPerPage = 20)
    {
        $promelec = new PromelecApiService();
        
        $params = [
            'status' => 1 // Только открытые счета
        ];
        
        $response = $promelec->getOrders($params);
        
        $customerId = env('PROM_ID');
        
        $data = collect($response)
            ->filter(fn($bill) => $bill->customer_id == $customerId)
            ->map(fn($bill) => $this->formatBill($bill))
            ->values();
        
        // Простая пагинация на стороне клиента
        $per_page = $itemsPerPage ?? 20;
        $current_page = $this->page;
        $total = $data->count();
        $last_page = (int) ceil($total / $per_page);
        $from = ($current_page - 1) * $per_page + 1;
        $to = $current_page * $per_page;
        $to = $to > $total ? $total : $to;
        
        $data = $data->slice(($current_page - 1) * $per_page, $per_page)->values();
        
        return collect(
            compact('current_page', 'data', 'from', 'last_page', 'per_page', 'to', 'total')
        );
    }

    /**
     * Получение строк счета Promelec
     * @param string $orderId - ID счета (bill_id)
     * @return array
     * @throws \Exception
     */
    public function getLines(string $orderId)
    {
        $promelec = new PromelecApiService();
        
        // Получаем позиции счета напрямую по bill_id
        try {
            $response = $promelec->getBillItems((int)$orderId);
            
            $lines = collect($response)
                ->map(fn($item) => $this->formatBillItem($item))
                ->values()
                ->toArray();
            
            return [
                'lines' => $lines,
                'total' => count($lines),
            ];
        } catch (\Exception $e) {
            // Возвращаем пустой массив если не удалось получить позиции
            return [
                'lines' => [],
                'total' => 0,
            ];
        }
    }

    /**
     * Получение информации о счете Promelec
     * @param string $orderId - ID счета (bill_id)
     * @return array
     * @throws \Exception
     */
    public function getOrder(string $orderId)
    {
        $promelec = new PromelecApiService();
        
        $bills = $promelec->getOrders(['status' => 1]);
        $bill = collect($bills)->firstWhere('bill_id', (int)$orderId);
        
        if (!$bill) {
            throw new \Exception('Bill not found');
        }
        
        return $this->formatBill($bill);
    }

    /**
     * Создание нового заказа (заглушка - Promelec не поддерживает создание через API)
     * @param $request
     * @return array
     * @throws \Exception
     */
    public function create($request)
    {
        $data = is_array($request) ? $request : ($request->item ?? []);

        $clientComment = $data['client_comment']
            ?? $data['comment']
            ?? $data['remark']
            ?? ($data['description'] ?? null);

        $params = [];

        if ($clientComment !== null) {
            $params['client_comment'] = $clientComment;
        }

        try {
            $promelec = new PromelecApiService();
            $response = $promelec->createBill($params);

            $bill = $response;

            if (is_object($response) && property_exists($response, 'result')) {
                $bill = $response->result;
            }

            return $this->formatBill($bill);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Добавление строк в заказ (заглушка - Promelec не поддерживает)
     * @param string $salesId - ID заказа
     * @param array $lines - массив строк для добавления
     * @param bool $reserveAll - резервировать все строки
     * @return mixed
     * @throws \Exception
     */
    public function addLines(string $salesId, array $lines, bool $reserveAll = true)
    {
        if (empty($lines)) {
            throw new \Exception('Line data is required');
        }

        $line = $lines[0];

        $itemId = $line['item_id'] ?? $line['itemId'] ?? null;
        $quantity = $line['quant'] ?? $line['qty'] ?? $line['quantity'] ?? null;

        if (!$itemId || !$quantity) {
            throw new \Exception('item_id and quantity are required for Promelec');
        }

        $params = [
            'bill_id' => (int)$salesId,
            'item_id' => $itemId,
            'quant' => (int)$quantity,
        ];

        if (isset($line['vendor_id'])) {
            $params['vendor_id'] = (int)$line['vendor_id'];
        } elseif (isset($line['vendorId'])) {
            $params['vendor_id'] = (int)$line['vendorId'];
        }

        try {
            $promelec = new PromelecApiService();
            $response = $promelec->addBillItem($params);

            $items = $response;

            if (is_object($response) && property_exists($response, 'result')) {
                $items = $response->result;
            }

            if (!is_array($items)) {
                $items = [$items];
            }

            $lines = collect($items)
                ->map(fn($item) => $this->formatBillItem($item))
                ->values()
                ->toArray();

            return [
                'lines' => $lines,
                'total' => count($lines),
            ];
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Изменение количества в строке заказа (заглушка - Promelec не поддерживает)
     * @param string $salesId - ID заказа
     * @param string $lineId - ID строки
     * @param int $quantity - новое количество
     * @return mixed
     * @throws \Exception
     */
    public function updateLineQuantity(string $salesId, string $lineId, int $quantity)
    {
        $params = [
            'bill_id' => (int)$salesId,
            'id' => $lineId,
            'quant' => (int)$quantity,
        ];

        try {
            $promelec = new PromelecApiService();
            $response = $promelec->updateBillItemQuantity($params);

            $items = $response;

            if (is_object($response) && property_exists($response, 'result')) {
                $items = $response->result;
            }

            if (!is_array($items)) {
                $items = [$items];
            }

            $line = collect($items)
                ->map(fn($item) => $this->formatBillItem($item))
                ->first();

            return [
                'line' => $line,
            ];
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Удаление строки заказа (заглушка - Promelec не поддерживает)
     * @param string $salesId - ID заказа
     * @param string $lineId - ID строки
     * @return mixed
     * @throws \Exception
     */
    public function deleteLine(string $salesId, string $lineId)
    {
        $params = [
            'bill_id' => (int)$salesId,
            'id' => $lineId,
        ];

        try {
            $promelec = new PromelecApiService();
            $promelec->deleteBillItem($params);

            return [
                'success' => true,
            ];
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Отправка счета (заглушка - Promelec не поддерживает)
     * @param string $salesId - ID заказа
     * @return mixed
     * @throws \Exception
     */
    public function sendInvoice(string $salesId)
    {
        throw new \Exception('Send invoice is not supported for Promelec');
    }

    /**
     * Отгрузка заказа (заглушка - Promelec не поддерживает)
     * @param string $salesId - ID заказа
     * @param string|null $customerDeliveryTypeId - ID способа доставки
     * @param string|null $dateDeadline - дата действия (формат YYYY-MM-DD)
     * @return mixed
     * @throws \Exception
     */
    public function shipOrder(string $salesId, ?string $customerDeliveryTypeId, ?string $dateDeadline)
    {
        throw new \Exception('Ship order is not supported for Promelec');
    }
}

