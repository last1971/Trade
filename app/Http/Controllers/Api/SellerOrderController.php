<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\ModelRequest;
use App\Services\SellerOrderService;

class SellerOrderController extends ModelController
{
    public function __construct()
    {
        parent::__construct(SellerOrderService::class);
    }

    /**
     * Добавление строк в заказ поставщика
     * @param ModelRequest $request
     * @param string $id - ID заказа
     * @return \Illuminate\Http\JsonResponse
     */
    public function addLines(ModelRequest $request, $id)
    {
        try {
            /** @var SellerOrderService $service */
            $service = $this->service;
            $result = $service->addLines($id, $request->line);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Получение строк заказа поставщика
     * @param \Illuminate\Http\Request $request
     * @param string $id - ID заказа
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLines(\Illuminate\Http\Request $request, $id)
    {
        try {
            /** @var SellerOrderService $service */
            $service = $this->service;
            $sellerId = $request->get('seller_id');
            
            if (!$sellerId) {
                return response()->json([
                    'message' => 'seller_id is required'
                ], 400);
            }
            
            $result = $service->getLines($id, (int)$sellerId);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Изменение количества в строке заказа
     * @param ModelRequest $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateLineQuantity(ModelRequest $request, $id)
    {
        try {
            /** @var SellerOrderService $service */
            $service = $this->service;
            
            $result = $service->updateLineQuantity(
                $id,
                $request->input('line_id'),
                $request->input('quantity'),
                $request->input('seller_id')
            );
            
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Удаление строки заказа
     * @param \Illuminate\Http\Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteLine(\Illuminate\Http\Request $request, $id)
    {
        try {
            /** @var SellerOrderService $service */
            $service = $this->service;
            
            $lineId = $request->get('line_id');
            $sellerId = $request->get('seller_id');
            
            if (!$lineId || !$sellerId) {
                return response()->json([
                    'message' => 'line_id and seller_id are required'
                ], 400);
            }
            
            $result = $service->deleteLine($id, $lineId, (int)$sellerId);
            
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Отправка счета
     * @param \Illuminate\Http\Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendInvoice(\Illuminate\Http\Request $request, $id)
    {
        try {
            /** @var SellerOrderService $service */
            $service = $this->service;
            
            $sellerId = $request->get('seller_id');
            
            if (!$sellerId) {
                return response()->json([
                    'message' => 'seller_id is required'
                ], 400);
            }
            
            $result = $service->sendInvoice($id, (int)$sellerId);
            
            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Отгрузка заказа
     * @param \Illuminate\Http\Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function shipOrder(\Illuminate\Http\Request $request, $id)
    {
        try {
            /** @var SellerOrderService $service */
            $service = $this->service;
            
            $sellerId = $request->get('seller_id');
            
            if (!$sellerId) {
                return response()->json([
                    'message' => 'seller_id is required'
                ], 400);
            }
            
            $customerDeliveryTypeId = $request->get('customer_delivery_type_id');
            $dateDeadline = $request->get('date_deadline');
            
            $result = $service->shipOrder($id, (int)$sellerId, $customerDeliveryTypeId, $dateDeadline);
            
            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
