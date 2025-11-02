<?php

namespace App\Interfaces;

use App\Http\Requests\SellerPriceRequest;

interface ISellerPriceable
{
    public function searchFromRequest(SellerPriceRequest $request): array;
}
