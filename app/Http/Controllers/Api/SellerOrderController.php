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
}
