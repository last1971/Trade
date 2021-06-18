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
    public function paginate(int $itemsPerPage = 20)
    {
        $compel = new CompelApiService();
        $params = [];
        $current_page = $this->page;
        $params['page_num'] = $current_page;
        $per_page = $itemsPerPage;
        $params['records_per_page'] = $per_page;
        if ($this->index !== false && $this->filterValues[$this->index] === 'false') {
            $params['status_filter'] = 'backorder';
        }
        $response = $compel->orders($params);
        $data = collect((array)$response->result->sales)
            ->map(fn($order) => [
                'id' => $order->sales_id,
                'date' => $order->sales_date,
                'number' => $order->document_number,
                'remark' => $order->comment,
                'amount' => $order->sales_amount > 0 ? $order->sales_amount : $order->amount,
                'closed' => $order->sales_status !== 'Открытый заказ',
            ]);
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
