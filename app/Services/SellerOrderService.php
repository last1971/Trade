<?php


namespace App\Services;


use App\SellerOrder;
use Exception;

class SellerOrderService extends ModelService
{
    /**
     * SellerOrderService constructor.
     */
    public function __construct()
    {
        parent::__construct(SellerOrder::class);
    }

    public function index($request)
    {
        $index = array_search('seller_id', $request->get('filterAttributes'));
        throw_if($index === false, new Exception('Need SellerId'));
        $compelId = config('pricing.Compel.sellerId');
        switch ($request->get('filterValues')[$index]) {
            case $compelId:
                $s = new CompelOrderService();
                return $s->index($request);
            default:
                return parent::index($request);
        }

    }

    /**
     * Создание заказа поставщика
     * @param $request
     * @return array|mixed
     * @throws Exception
     * @throws \App\Exceptions\CompelException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function create($request)
    {
        $data = is_array($request) ? $request : $request->item;
        
        // Определяем поставщика по seller_id
        if (empty($data['seller_id'])) {
            throw new Exception('seller_id is required');
        }
        
        $compelId = config('pricing.Compel.sellerId');
        
        // Если это Compel - делегируем в CompelOrderService
        if ($data['seller_id'] == $compelId) {
            $s = new CompelOrderService();
            return $s->create($request);
        }
        
        // Иначе стандартное сохранение в БД
        return parent::create($request);
    }

    /**
     * Добавление строк в заказ поставщика
     * @param string $orderId - ID заказа
     * @param array $line - данные строки (должен содержать seller_id)
     * @return array|mixed
     * @throws Exception
     * @throws \App\Exceptions\CompelException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function addLines(string $orderId, array $line)
    {
        // Проверяем seller_id в данных строки
        if (empty($line['seller_id'])) {
            throw new Exception('seller_id is required');
        }
        
        $compelId = config('pricing.Compel.sellerId');
        
        // Если это Compel - делегируем в CompelOrderService
        if ($line['seller_id'] == $compelId) {
            $s = new CompelOrderService();
            return $s->addLines($orderId, [$line]);
        }
        
        // Иначе стандартная логика для БД
        throw new Exception('Adding lines to non-Compel orders is not implemented');
    }
}
