<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class ConfigController extends Controller
{
    public function index()
    {
        return response()->json([
            'priceCoefficients' => [
                config('pricing.Compel.sellerId') => (float) config('pricing.Compel.cbrCoefficient', 1),
                config('pricing.CompelDms.sellerId') => (float) config('pricing.CompelDms.cbrCoefficient', 1),
            ]
        ]);
    }
}
