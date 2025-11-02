<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SellerPriceRequest;
use App\Interfaces\ISellerPriceable;
use App\Seller;
use App\Services\Pricing\ElcoPro;
use App\Services\SellerPriceHttpService;
use App\Services\SellerPriceService;
use Illuminate\Support\Collection;

class SellerPriceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param SellerPriceRequest $request
     * @param ISellerPriceable $service
     * @return array
     */
    public function index(SellerPriceRequest $request, ISellerPriceable $service): array
    {
        return $service->searchFromRequest($request);
    }

    /**
     * Display a listing of the resource.
     *
     * @param SellerPriceRequest $request
     * @param SellerPriceService $service
     * @return array
     * @throws \Throwable
     */
    public function show(SellerPriceRequest $request, SellerPriceService $service): array
    {
        return $service->searchFromRequest($request);
    }

    public function sellers(SellerPriceHttpService $service)
    {
        /*
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
        );*/
        $sellers = $service->getSellers();
        if (!request()->user()->hasPermissionTo('seller-price.full')) {
            $sellers = array_map(function ($seller) {
                unset($seller['name']);
                return $seller;
            }, $sellers);
        }
        return $sellers;
    }
}
