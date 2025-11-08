<?php


namespace App\Services;


class CompelOrderService
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
}
