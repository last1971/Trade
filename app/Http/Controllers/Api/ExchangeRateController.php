<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExchangeRateRequest;
use App\Services\RateExchangeService;
use Carbon\Carbon;

class ExchangeRateController extends Controller
{
    //
    public function index(ExchangeRateRequest $request, RateExchangeService $service)
    {
        return $service->getOnDate(new Carbon($request->date));
    }
}
