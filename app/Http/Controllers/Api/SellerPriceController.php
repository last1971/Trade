<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SellerPriceRequest;
use App\Seller;
use App\Services\SellerPriceService;

class SellerPriceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return array
     */
    public function index(SellerPriceRequest $request, SellerPriceService $service)
    {
        return $service->get($request->search, $request->sellerId, $request->isFile, $request->isUpdate);
    }

    public function sellers()
    {
        $sellers = Seller::query()
            ->whereNotNull(['IS_API', 'IS_FILE'])
            ->whereOr(['IS_API' => true, 'IS_FILE' => true])
            ->select('WHEREISPOSTCODE as sellerId', 'NAMEPOST as name', 'IS_API as isApi', 'IS_FILE as isFile')
            ->get()
            ->toArray();
        array_unshift(
            $sellers,
            [
                'sellerId' => 0,
                'name' => 'ЭлкоПро/Склад',
                'isApi' => 1,
                'isFile' => 0,
            ],
        );
        return $sellers;
    }
}
