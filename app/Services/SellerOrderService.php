<?php


namespace App\Services;


use App\SellerOrder;
use Exception;

class SellerOrderService extends ModelService
{
    /**
     * SellerOrderService constructor.
     */
    public function __construct()
    {
        parent::__construct(SellerOrder::class);
    }

    public function index($request)
    {
        $index = array_search('seller_id', $request->get('filterAttributes'));
        throw_if($index === false, new Exception('Need SellerId'));
        switch ($request->get('filterValues')[$index]) {
            case 1:
                $s = new CompelOrderService();
                return $s->index($request);
            default:
                return parent::index($request);
        }

    }
}
