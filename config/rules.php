<?php

return [
    'checkToken' => [
        'token' => 'required|string',
    ],
    'forgot' => [
        'email' => 'required|string|email'
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
    'user.update' => [
        'item' => 'array|required',
        'item.name' => 'string|required',
        'item.employeeId' => 'integer',
        'item.rules' => 'array',
        'options.with' => 'array',
        'options.with.*' => 'string',
    ],
];
