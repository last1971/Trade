<?php

namespace App\Http\Resources;

use App\SellerPriceRule;
use Illuminate\Http\Resources\Json\ResourceCollection;

class SellerPriceCollection extends ResourceCollection
{
    public function __construct($resource, SellerPriceRule $rule)
    {
        request()->merge(['rule' => $rule]);
        parent::__construct($resource);
    }

}
