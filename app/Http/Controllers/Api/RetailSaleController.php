<?php


namespace App\Http\Controllers\Api;


use App\Http\Requests\IndexRequest;
use App\Services\RetailSaleService;

class RetailSaleController extends ModelController
{
    public function __construct()
    {
        parent::__construct(RetailSaleService::class);
    }

    public function index(IndexRequest $request)
    {
        $this->service->setRawFrom('select_outday1(?, null) as retail_sales', [$request->date]);
        $request->merge(['selectAttributes' => '*']);
        return parent::index($request);
    }
}
