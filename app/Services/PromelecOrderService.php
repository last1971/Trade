<?php

namespace App\Services;

use App\Interfaces\ISellerOrderService;
use Illuminate\Support\Facades\Log;

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
            ->map(fn($bill) => [
                'id' => $bill->bill_id,
                'seller_id' => config('pricing.Promelec.sellerId'),
                'date' => $this->convertDate($bill->date_),
                'number' => $bill->number ?? $bill->bill_id,
                'remark' => $bill->client_comment ?? '',
                'amount' => $bill->sum_ ?? 0,
                'closed' => false,
                'document_number' => null,
                'date_deadline' => null,
            ])
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
                ->map(function($item) {
                    return [
                        'line_id' => $item->id ?? null,
                        'item_id' => $item->item_id ?? null,
                        'item_name' => $item->name ?? '',
                        'brend' => '',
                        'package_name' => '',
                        'sales_qty' => (int)($item->quant ?? 0),
                        'price' => (float)($item->price ?? 0),
                        'amount' => (float)($item->sum_ ?? 0),
                        'currency_code' => 'RUB',
                        'reserve_qty' => (int)($item->quant ?? 0),
                        'reservation_end' => null,
                        'status' => $item->statusname ?? '',
                    ];
                })
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
        
        return [
            'id' => $bill->bill_id,
            'seller_id' => config('pricing.Promelec.sellerId'),
            'date' => $this->convertDate($bill->date_),
            'number' => $bill->number ?? $bill->bill_id,
            'remark' => $bill->client_comment ?? '',
            'amount' => $bill->sum_ ?? 0,
            'closed' => false,
        ];
    }

    /**
     * Создание нового заказа (заглушка - Promelec не поддерживает создание через API)
     * @param $request
     * @return array
     * @throws \Exception
     */
    public function create($request)
    {
        throw new \Exception('Creating orders is not supported for Promelec');
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
        throw new \Exception('Adding lines is not supported for Promelec');
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
        throw new \Exception('Updating line quantity is not supported for Promelec');
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
        throw new \Exception('Deleting line is not supported for Promelec');
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
}

