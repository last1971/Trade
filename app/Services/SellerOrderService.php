<?php


namespace App\Services;


use App\SellerOrder;
use Exception;

class SellerOrderService extends ModelService
{
    /**
     * @var \Illuminate\Support\Collection
     */
    private $orderServices;

    /**
     * SellerOrderService constructor.
     */
    public function __construct(
        CompelOrderService $compelOrderService = null,
        PromelecOrderService $promelecOrderService = null
    )
    {
        parent::__construct(SellerOrder::class);
        
        $this->orderServices = collect([
            config('pricing.Compel.sellerId') => $compelOrderService ?? new CompelOrderService(),
            config('pricing.Promelec.sellerId') => $promelecOrderService ?? new PromelecOrderService(),
        ]);
    }

    public function index($request)
    {
        $index = array_search('seller_id', $request->get('filterAttributes'));
        throw_if($index === false, new Exception('Need SellerId'));
        
        $sellerId = $request->get('filterValues')[$index];
        
        $service = $this->orderServices->get($sellerId);
        
        if ($service) {
            return $service->index($request);
        }
        
        return parent::index($request);
    }

    /**
     * Вспомогательный метод для определения сервиса по seller_id
     * @param int $sellerId
     * @return \App\Interfaces\ISellerOrderService|null
     */
    private function getOrderServiceBySeller(int $sellerId)
    {
        return $this->orderServices->get($sellerId);
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
        
        $service = $this->getOrderServiceBySeller($data['seller_id']);
        
        if ($service) {
            return $service->create($request);
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
        
        $service = $this->getOrderServiceBySeller($line['seller_id']);
        
        if ($service) {
            return $service->addLines($orderId, [$line]);
        }
        
        // Иначе стандартная логика для БД
        throw new Exception('Adding lines to orders is not implemented for this seller');
    }

    /**
     * Получение строк заказа поставщика
     * @param string $orderId - ID заказа
     * @param int $sellerId - ID поставщика
     * @return array
     * @throws Exception
     * @throws \App\Exceptions\CompelException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getLines(string $orderId, int $sellerId)
    {
        $service = $this->getOrderServiceBySeller($sellerId);
        
        if ($service) {
            return $service->getLines($orderId);
        }
        
        // Иначе стандартная логика для БД
        throw new Exception('Getting lines from orders is not implemented for this seller');
    }

    /**
     * Изменение количества в строке заказа
     * @param string $orderId
     * @param string $lineId
     * @param int $quantity
     * @param int $sellerId
     * @return mixed
     * @throws Exception
     * @throws \App\Exceptions\CompelException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function updateLineQuantity(string $orderId, string $lineId, int $quantity, int $sellerId)
    {
        $service = $this->getOrderServiceBySeller($sellerId);
        
        if ($service && method_exists($service, 'updateLineQuantity')) {
            return $service->updateLineQuantity($orderId, $lineId, $quantity);
        }
        
        throw new Exception('Updating line quantity is not implemented for this seller');
    }

    /**
     * Удаление строки заказа
     * @param string $orderId
     * @param string $lineId
     * @param int $sellerId
     * @return mixed
     * @throws Exception
     * @throws \App\Exceptions\CompelException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function deleteLine(string $orderId, string $lineId, int $sellerId)
    {
        $service = $this->getOrderServiceBySeller($sellerId);
        
        if ($service && method_exists($service, 'deleteLine')) {
            return $service->deleteLine($orderId, $lineId);
        }
        
        throw new Exception('Deleting line is not implemented for this seller');
    }

    /**
     * Отправка счета
     * @param string $orderId
     * @param int $sellerId
     * @return mixed
     * @throws Exception
     * @throws \App\Exceptions\CompelException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendInvoice(string $orderId, int $sellerId)
    {
        $service = $this->getOrderServiceBySeller($sellerId);
        
        if ($service && method_exists($service, 'sendInvoice')) {
            return $service->sendInvoice($orderId);
        }
        
        throw new Exception('Send invoice not implemented for this seller');
    }
}
