<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MpnPartRequest;
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

    /**
     * Справочная карточка детали с mpn.cc по MPN из строки прайса.
     * Код ответа сервиса отдаём наружу как есть: диалогу нужны и 503 с blockedUntil, и 502.
     */
    public function mpn(MpnPartRequest $request, SellerPriceHttpService $service)
    {
        $result = $service->getMpnPart(
            $request->get('q'),
            $request->get('manufacturer'),
            $request->boolean('refresh'),
        );
        return response()->json($result['body'], $result['status']);
    }

    public function blocked(SellerPriceHttpService $service)
    {
        return $service->getBlocked();
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
