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
        return $service->searchFromRequest($request);
    }

    public function sellers()
    {
        $sellers = Seller::query()
            ->whereNotNull(['IS_API'])
            ->whereOr(['IS_API' => true])
            ->select('WHEREISPOSTCODE as sellerId', 'NAMEPOST as name', 'IS_API as isApi')
            ->get()
            ->toArray();
        array_unshift(
            $sellers,
            [
                'sellerId' => 0,
                'name' => 'ЭлкоПро',
                'isApi' => 1,
            ],
        );
        if (!request()->user()->hasPermissionTo('seller-price.full')) {
            $sellers = array_map(function ($seller) {
                unset($seller['name']);
                return $seller;
            }, $sellers);
        }
        return $sellers;
    }
}
