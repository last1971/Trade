<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SellerPriceRule extends Model
{
    use HasFactory;

    protected $connection = 'mysql';

    static public function userSellerPriceRule()
    {
        return request()->user()->hasPermissionTo('seller-price.full')
            ? self::query()->firstWhere('alias', 'full_rule')
            : request()->user()->sellerPriceRule
            ?? self::query()->firstWhere('alias', 'buyer_rule');
    }
}
