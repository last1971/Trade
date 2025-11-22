<?php

namespace App\Services;

use App\Interfaces\ISellerOrderService;
use App\Order;
use App\OrderLine;
use Exception;

class FirebirdOrderService implements ISellerOrderService
{
    // Статусы заказов
    const STATUS_FORMING = 0;        // Формируется
    const STATUS_FORMED = 1;         // Сформирован
    const STATUS_IN_WAY = 2;         // В Пути
    const STATUS_PARTIALLY = 3;      // Частично пришел
    const STATUS_ARRIVED = 4;        // Пришел
    const STATUS_CLOSED = 5;         // Закрыт
    const STATUS_TO_EXECUTE = 6;     // К выполнению

    protected $orderService;
    protected $page = 1;
    protected $sellerId;

    public function __construct()
    {
        $this->orderService = new OrderService();
    }

    /**
     * Получение списка заказов
     * @param $request
     * @return $this
     */
    public function index($request)
    {
        $this->page = $request->get('page', 1);

        // Получаем seller_id из запроса
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
     * Пагинация заказов
     * @param int|null $itemsPerPage
     * @return \Illuminate\Support\Collection
     */
    public function paginate(?int $itemsPerPage = 20)
    {
        // Получаем формирующиеся заказы (STATUS = 0)
        $query = Order::where('WHEREISPOSTCODE', $this->sellerId)
            ->where('STATUS', self::STATUS_FORMING)
            ->orderBy('DATA_ZAK', 'desc');

        $total = $query->count();
        $per_page = $itemsPerPage ?? 20;
        $current_page = $this->page;
        $last_page = (int) ceil($total / $per_page);

        $orders = $query
            ->skip(($current_page - 1) * $per_page)
            ->take($per_page)
            ->get();

        // Преобразуем в формат API
        $data = $orders->map(function($order) {
            return [
                'id' => $order->ID,
                'seller_id' => $order->WHEREISPOSTCODE,
                'date' => $order->DATA_ZAK,
                'number' => $order->INVOICE_NUM ?? $order->ID,
                'remark' => $order->PRIM ?? '',
                'amount' => $this->calculateOrderAmount($order->ID),
                'closed' => $order->STATUS >= self::STATUS_CLOSED,
            ];
        });

        $from = $total > 0 ? ($current_page - 1) * $per_page + 1 : 0;
        $to = min($current_page * $per_page, $total);

        return collect([
            'current_page' => $current_page,
            'data' => $data,
            'from' => $from,
            'last_page' => $last_page,
            'per_page' => $per_page,
            'to' => $to,
            'total' => $total,
        ]);
    }

    /**
     * Создание нового заказа
     * @param $request
     * @return array
     */
    public function create($request)
    {
        $data = is_array($request) ? $request : $request->item;

        $order = Order::create([
            'WHEREISPOSTCODE' => $data['seller_id'],
            'DATA_ZAK' => now()->format('Y-m-d'),
            'PRIM' => $data['remark'] ?? '',
            'STATUS' => self::STATUS_FORMING,
            'STAFF_ID' => auth()->user()->employee_id ?? null,
        ]);

        return [
            'id' => $order->ID,
            'seller_id' => $order->WHEREISPOSTCODE,
            'date' => $order->DATA_ZAK,
            'number' => $order->ID,
            'remark' => $order->PRIM,
            'amount' => 0,
            'closed' => false,
        ];
    }

    /**
     * Добавление строк в заказ
     * @param string $orderId
     * @param array $lines
     * @param bool $reserveAll
     * @return array
     * @throws Exception
     */
    public function addLines(string $orderId, array $lines, bool $reserveAll = true)
    {
        $createdLines = [];

        foreach ($lines as $line) {
            // КРИТИЧНО: Проверяем наличие привязки к товару
            if (empty($line['good_id'])) {
                throw new Exception('Позиция должна быть привязана к товару в БД (good_id обязателен)');
            }

            $price = $line['price'] ?? 0;
            $qty = $line['qty'];
            $amount = $price * $qty;

            $orderLine = OrderLine::create([
                'MASTER_ID' => $orderId,
                'GOODSCODE' => $line['good_id'],
                'QUAN' => $qty,
                'PRICE' => $price,
                'SUMMAP' => $amount,
                'NAME_IN_PRICE' => $line['item_name'] ?? '',
                'STAFF_ID' => auth()->user()->employee_id ?? null,
                'PRIM' => $line['remark'] ?? '',
            ]);

            $createdLines[] = [
                'line_id' => $orderLine->ID,
                'item_id' => null,
                'item_name' => $orderLine->NAME_IN_PRICE,
                'sales_qty' => $orderLine->QUAN,
                'good_id' => $orderLine->GOODSCODE,
                'price' => $orderLine->PRICE,
                'amount' => $orderLine->SUMMAP,
                'currency_code' => 'RUB',
            ];
        }

        return [
            'success' => true,
            'lines' => $createdLines
        ];
    }

    /**
     * Получение строк заказа
     * @param string $orderId
     * @return array
     */
    public function getLines(string $orderId)
    {
        $lines = OrderLine::where('MASTER_ID', $orderId)
            ->with('good')
            ->get();

        return [
            'lines' => $lines->map(function($line) {
                return [
                    'line_id' => $line->ID,
                    'item_id' => null,
                    'item_name' => $line->NAME_IN_PRICE,
                    'sales_qty' => $line->QUAN,
                    'good_id' => $line->GOODSCODE,
                    'price' => $line->PRICE ?? 0,
                    'amount' => $line->SUMMAP ?? 0,
                    'currency_code' => 'RUB',
                    'remark' => $line->PRIM,
                ];
            })->toArray(),
            'total' => $lines->count(),
        ];
    }

    /**
     * Получение информации о заказе
     * @param string $orderId
     * @return array
     */
    public function getOrder(string $orderId)
    {
        $order = Order::findOrFail($orderId);

        return [
            'id' => $order->ID,
            'seller_id' => $order->WHEREISPOSTCODE,
            'date' => $order->DATA_ZAK,
            'number' => $order->INVOICE_NUM ?? $order->ID,
            'remark' => $order->PRIM ?? '',
            'amount' => $this->calculateOrderAmount($order->ID),
            'closed' => $order->STATUS >= self::STATUS_CLOSED,
        ];
    }

    /**
     * Изменение количества в строке заказа
     * @param string $orderId
     * @param string $lineId
     * @param int $quantity
     * @return array
     */
    public function updateLineQuantity(string $orderId, string $lineId, int $quantity)
    {
        $line = OrderLine::findOrFail($lineId);
        $line->update(['QUAN' => $quantity]);

        return ['success' => true];
    }

    /**
     * Удаление строки заказа
     * @param string $orderId
     * @param string $lineId
     * @return array
     */
    public function deleteLine(string $orderId, string $lineId)
    {
        OrderLine::destroy($lineId);

        return ['success' => true];
    }

    /**
     * Отправка счета (генерация Excel для Firebird заказов)
     * @param string $orderId
     * @return array
     * @throws Exception
     */
    public function sendInvoice(string $orderId)
    {
        // Проверяем наличие строк
        $linesCount = OrderLine::where('MASTER_ID', $orderId)->count();
        if ($linesCount === 0) {
            throw new Exception('Заказ пуст');
        }

        // Генерируем Excel файл
        $export = new \App\Exports\SellerOrderLinesExport($orderId);
        $excel = \Maatwebsite\Excel\Facades\Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX);

        // Возвращаем Excel как base64
        return [
            'success' => true,
            'data' => [
                'type' => 'excel',
                'excel' => base64_encode($excel),
                'filename' => 'order_' . $orderId . '.xlsx',
            ]
        ];
    }

    /**
     * Отгрузка заказа (смена статуса на "Сформирован")
     * @param string $orderId
     * @param string|null $customerDeliveryTypeId
     * @param string|null $dateDeadline
     * @return array
     */
    public function shipOrder(string $orderId, ?string $customerDeliveryTypeId, ?string $dateDeadline)
    {
        $order = Order::findOrFail($orderId);
        $order->update(['STATUS' => self::STATUS_FORMED]);

        return ['success' => true];
    }

    /**
     * Подсчет суммы заказа из строк
     * @param int $orderId
     * @return float
     */
    private function calculateOrderAmount(int $orderId)
    {
        // TODO: реализовать подсчет суммы из строк заказа когда будет привязка к прайсу
        return OrderLine::where('MASTER_ID', $orderId)->count() * 0;
    }
}
