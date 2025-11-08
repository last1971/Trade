<?php

namespace App\Http\Controllers\Api;

use App\Services\CompelApiService;
use Illuminate\Http\JsonResponse;

class CompelController
{
    /**
     * Получение методов отгрузки Compel
     * @return JsonResponse
     */
    public function getDeliveryModes(): JsonResponse
    {
        try {
            $compel = new CompelApiService();
            $response = $compel->getDeliveryModes();
            
            return response()->json([
                'success' => true,
                'data' => $response->result ?? []
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}

