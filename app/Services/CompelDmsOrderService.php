<?php

namespace App\Services;

use App\Interfaces\ISellerOrderService;
use App\SellerOrder;
use App\SellerOrderLine;
use Exception;

class CompelDmsOrderService implements ISellerOrderService
{
    protected $page = 1;
    protected $sellerId;

    /**
     * Получение списка заказов (всегда один DMS заказ)
     * @param $request
     * @return $this
     */
    public function index($request)
    {
        $this->page = $request->get('page', 1);

        $filterAttributes = $request->get('filterAttributes', []);
        $filterValues = $request->get('filterValues', []);

        $sellerIndex = array_search('seller_id', $filterAttributes);
        if ($sellerIndex === false) {
            throw new Exception('seller_id is required');
        }

        $this->sellerId = $filterValues[$sellerIndex];

        return $this;
    }

    /**
     * Пагинация (возвращает все незакрытые DMS заказы)
     * @param int|null $itemsPerPage
     * @return \Illuminate\Support\Collection
     */
    public function paginate(?int $itemsPerPage = 20)
    {
        // Получаем все незакрытые заказы для DMS
        $query = SellerOrder::where('seller_id', $this->sellerId)
            ->where('closed', false)
            ->orderBy('created_at', 'desc');

        $total = $query->count();
        $per_page = $itemsPerPage ?? 20;
        $current_page = $this->page;
        $last_page = (int) ceil($total / $per_page);

        $orders = $query
            ->skip(($current_page - 1) * $per_page)
            ->take($per_page)
            ->get();

        $from = $total > 0 ? ($current_page - 1) * $per_page + 1 : 0;
        $to = min($current_page * $per_page, $total);

        return collect([
            'current_page' => $current_page,
            'data' => $orders,
            'from' => $from,
            'last_page' => $last_page,
            'per_page' => $per_page,
            'to' => $to,
            'total' => $total,
        ]);
    }

    /**
     * Создание DMS заказа (всегда создает новый)
     * @param $request
     * @return array
     */
    public function create($request)
    {
        $data = is_array($request) ? $request : $request->item;

        // Создаем новый DMS заказ
        $order = SellerOrder::create([
            'seller_id' => $data['seller_id'],
            'date' => now(),
            'remark' => $data['remark'] ?? '',
            'closed' => false,
        ]);

        return $order->toArray();
    }

    /**
     * Добавление строк в DMS заказ
     * @param string $orderId
     * @param array $lines
     * @param bool $reserveAll
     * @return array
     */
    public function addLines(string $orderId, array $lines, bool $reserveAll = true)
    {
        $createdLines = [];

        foreach ($lines as $line) {
            $price = $line['price'] ?? 0;
            $qty = $line['qty'];

            $orderLine = SellerOrderLine::create([
                'seller_order_id' => $orderId,
                'item_id' => $line['item_id'],
                'item_name' => $line['item_name'] ?? '',
                'qty' => $qty,
                'price' => $price,
                'price_data' => $line['price_data'] ?? $line,
            ]);

            $createdLines[] = [
                'line_id' => $orderLine->id,
                'item_id' => $orderLine->item_id,
                'item_name' => $orderLine->item_name,
                'sales_qty' => $qty,
                'price' => $price,
                'amount' => $price * $qty,
                'currency_code' => 'RUB',
            ];
        }

        return [
            'success' => true,
            'lines' => $createdLines
        ];
    }

    /**
     * Получение строк DMS заказа
     * @param string $orderId
     * @return array
     */
    public function getLines(string $orderId)
    {
        $lines = SellerOrderLine::where('seller_order_id', $orderId)->get();

        return [
            'lines' => $lines->map(function($line) {
                $priceData = $line->price_data ?? [];

                return [
                    'line_id' => $line->id,
                    'item_id' => $line->item_id,
                    'item_name' => $line->item_name,
                    'sales_qty' => $line->qty,
                    'price' => $line->price,
                    'amount' => $line->price * $line->qty,
                    'currency_code' => 'RUB',
                    'brend' => $priceData['brend'] ?? null,
                    'package_name' => $priceData['package_name'] ?? null,
                    'reserve_qty' => $line->qty,
                    'reservation_end' => null,
                    'price_data' => $priceData,
                ];
            })->toArray(),
            'total' => $lines->count(),
        ];
    }

    /**
     * Получение информации о DMS заказе
     * @param string $orderId
     * @return array
     */
    public function getOrder(string $orderId)
    {
        $order = SellerOrder::findOrFail($orderId);
        return $order->toArray();
    }

    /**
     * Изменение количества в строке
     * @param string $orderId
     * @param string $lineId
     * @param int $quantity
     * @return array
     */
    public function updateLineQuantity(string $orderId, string $lineId, int $quantity)
    {
        $line = SellerOrderLine::findOrFail($lineId);
        $line->update(['qty' => $quantity]);

        return ['success' => true];
    }

    /**
     * Удаление строки
     * @param string $orderId
     * @param string $lineId
     * @return array
     */
    public function deleteLine(string $orderId, string $lineId)
    {
        SellerOrderLine::destroy($lineId);

        return ['success' => true];
    }

    /**
     * Отправка счета (генерация Excel для DMS заказов)
     * @param string $orderId
     * @return array
     * @throws Exception
     */
    public function sendInvoice(string $orderId)
    {
        // Проверяем наличие строк
        $linesCount = SellerOrderLine::where('seller_order_id', $orderId)->count();
        if ($linesCount === 0) {
            throw new Exception('Заказ пуст');
        }

        // Генерируем Excel файл
        $export = new \App\Exports\SellerOrderLinesDmsExport($orderId);
        $excel = \Maatwebsite\Excel\Facades\Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX);

        // Возвращаем Excel как base64
        return [
            'success' => true,
            'data' => [
                'type' => 'excel',
                'excel' => base64_encode($excel),
                'filename' => 'dms_order_' . $orderId . '.xlsx',
            ]
        ];
    }

    /**
     * Отгрузка DMS заказа = создание реального заказа в Compel API
     * @param string $orderId
     * @param string|null $customerDeliveryTypeId
     * @param string|null $dateDeadline
     * @return array
     * @throws Exception
     */
    public function shipOrder(string $orderId, ?string $customerDeliveryTypeId, ?string $dateDeadline)
    {
        $order = SellerOrder::findOrFail($orderId);

        // Получаем строки из MariaDB
        $localLines = SellerOrderLine::where('seller_order_id', $orderId)->get();

        if ($localLines->isEmpty()) {
            throw new Exception('Заказ пустой, нечего отгружать');
        }
    
        // Формируем строки для Compel API из price_data
        $salesLines = $localLines->map(function($line) {
            $priceData = $line->price_data;
            return [
                'qty' => $line->qty,
                // Используем данные из price_data для DMS полей
                'application_area_id' => $priceData['options']['application_area_id'] ?? '021',
                'item_name' => $priceData['name'] ?? $line->item_name,
                'invc_brend_alias' => $priceData['producer'] ?? null,
                'sales_price' => $priceData['price'],
                'prognosis_id' => $priceData['options']['prognosis_id'] ?? null,
                'min_qty' => $priceData['minQuantity'] ?? null,
                'max_qty' => $priceData['maxQuantity'] ?? null,
                'qty_multiples' => $priceData['multiplicity'] ?? null,
            ];
        })->toArray();

        // Создаем заказ в Compel API со всеми параметрами за один запрос
        $compel = new CompelApiService();

        $orderParams = [
            'comment' => $order->remark,
            'is_pick_all' => true,
            'auto_credit' => true,
            'sales_lines' => $salesLines,
        ];

        if ($customerDeliveryTypeId) {
            $orderParams['customer_delivery_type_id'] = $customerDeliveryTypeId;
        }

        if ($dateDeadline) {
            $orderParams['date_dead_line'] = $dateDeadline;
        }

        $response = $compel->createOrder($orderParams);

        $salesId = $response->result->sales_id ?? null;

        if (!$salesId) {
            throw new Exception('Не удалось создать заказ в Compel API');
        }

        // Закрываем локальный DMS заказ
        $order->update([
            'closed' => true,
            'number' => $salesId, // Сохраняем номер заказа в Compel
        ]);

        return [
            'success' => true,
            'sales_id' => $salesId,
            'message' => "Заказ DMS отгружен в Compel. Номер заказа: {$salesId}",
        ];
    }
}
