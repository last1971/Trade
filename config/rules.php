<?php
/*
$retailPrice = [
    'item' => 'array|required',
    'item.PRICEROZN' => 'required|numeric',
    'item.QUANMOPT' => ['nullable', 'integer', function ($attribute, $value, $fail) {
        if ($value && $value < 2)
            $fail('validation.invalid');
    }],
    'item.PRICEMOPT' => ['nullable', 'numeric', function ($attribute, $value, $fail) {
        if ($value && ($value >= request()->item['PRICEROZN'] || !request()->item['QUANMOPT']))
            $fail('validation.invalid');
    }],
    'item.QUANOPT' => ['nullable', 'integer', function ($attribute, $value, $fail) {
        if ($value && ($value < 2 || $value <= request()->item['QUANMOPT']))
            $fail('validation.invalid');
    }],
    'item.PRICEOPT' => ['nullable', 'numeric', function ($attribute, $value, $fail) {
        if ($value &&
            (!request()->item['QUANOPT'] ||
                $value >= request()->item['PRICEROZN'] ||
                $value >= request()->item['PRICEMOPT']
            )
        )
            $fail('validation.invalid');
    }],
    'options.with' => 'array',
    'options.with.*' => 'string',
];
*/
return [
    'advanced-buyer.store' => [
        'item' => 'array|required',
        'item.edo_id' => 'required|string|unique:App\AdvancedBuyer,edo_id',
        'item.buyer_id' => 'required|integer|unique:App\AdvancedBuyer,buyer_id',
        'options.with' => 'array',
        'options.with.*' => 'string',
    ],
    'advanced-buyer.update' => [
        'item' => 'array|required',
        'item.edo_id' => 'string|required',
        'item.buyer_id' => 'integer|required',
        'options.with' => 'array',
        'options.with.*' => 'string',
    ],
    'advanced-buyer.destroy' => [],
    'checkToken' => [
        'token' => 'required|string',
    ],
    'forgot' => [
        'email' => 'required|string|email'
    ],
    'good.update' => [
        'item' => 'array|required',
        'item.NAMECODE' => 'required|integer',
        'item.CATEGORYCODE' => 'required|integer',
        'item.UNIT_I' => 'required|string|max:10',
        'item.PRODUCER' => 'nullable|string|max:60',
        'item.BODY' => 'nullable|string|max:60',
        'item.PRIM' => 'nullable|string|max:60',
    ],
    'invoice.update' => [
        'item' => 'array|required',
        'item.STATUS' => 'integer',
        'item.DATA' => 'date',
        'item.NS' => 'integer',
        'item.FIRM_ID' => 'integer',
        'item.POKUPATCODE' => 'integer',
        'item.PRIM' => 'nullable|string',
        'options.with' => 'array',
        'options.with.*' => 'string',
    ],
    'invoice-line.update' => [
        'item' => 'array|required',
        'item.QUAN' => 'integer',
        'item.PRICE' => 'numeric',
        'item.SUMMAP' => 'numeric',
        'options.with' => 'array',
        'options.with.*' => 'string',
    ],
    'login' => [
        'email' => 'required|string|email|max:255',
        'password' => 'required|string|min:6',
    ],
    'name.store' => [
        'item' => 'array|required',
        'item.CATEGORYCODE' => 'integer|required',
       // 'item.NAME' => 'required|string|unique:firebird.NAME,NAME,NULL,id,CATEGORYCODE,' . request()->item['CATEGORYCODE'],
        'item.SERIA' => 'required|string',
        'options.with' => 'array',
        'options.with.*' => 'string',
    ],
    'order.store' => [
        'item' => 'array|required',
        'item.STATUS' => 'integer',
        'item.INVOICE_DATA' => 'required|date',
        'item.DATA_PRIH' => 'required|date',
        'item.INVOICE_NUM' => 'required|string',
        'item.WHEREISPOSTCODE' => 'required|integer',
        'options.with' => 'array',
        'options.with.*' => 'string',
    ],
    'order.update' => [
        'item' => 'array|required',
        'item.STATUS' => 'integer',
        'item.INVOICE_DATA' => 'date',
        'item.DATA_PRIH' => 'date',
        'item.INVOICE_NUM' => 'string',
        'item.WHEREISPOSTCODE' => 'integer',
        'options.with' => 'array',
        'options.with.*' => 'string',
    ],
    'register' => [
        'name' => 'required|string|max:255',
        'email' => 'required|string|email|max:255|unique:users',
        'password' => 'required|string|min:6|confirmed',
    ],
    'resetPassword' => [
        'email' => 'required|string|email',
        'password' => 'required|string|min:6|confirmed',
        'token' => 'required|string'
    ],
    'retail-price.store' => $retailPrice,
    'retail-price.update' => $retailPrice,
    'user.update' => [
        'item' => 'array|required',
        'item.name' => 'string|required',
        'item.employeeId' => 'integer',
        'item.rules' => 'array',
        'options.with' => 'array',
        'options.with.*' => 'string',
    ],
];
