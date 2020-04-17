<?php


namespace App\Services;


use App\Buyer;
use Illuminate\Support\Str;

class BuyerService extends ModelService
{
    public function __construct()
    {
        parent::__construct(Buyer::class);
    }

    public function index($request)
    {
        $res = parent::index($request);
        if ($request->user() && !$request->user()->userBuyers->isEmpty()) {
            $userBuyers = Str::replaceLast(
                ',', '',
                $request->user()->userBuyers->reduce(
                    function ($carry, $buyer) {
                        return $carry . $buyer->buyer_id . ',';
                    },
                    ''
                )
            );
            $res->whereRaw('POKUPAT.POKUPATCODE IN (' . $userBuyers . ')');
        }
        return $res;
    }
}
