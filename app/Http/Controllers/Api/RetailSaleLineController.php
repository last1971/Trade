<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\RefundRequest;
use App\Services\RetailSaleLineService;

class RetailSaleLineController extends ModelController
{
    public function __construct()
    {
        parent::__construct(RetailSaleLineService::class);
    }

    /**
     * @param RefundRequest $request
     * @return string
     */
    public function refund(RefundRequest $request)
    {
        $this->service->refund($request);
        return 'OK';
    }
}
