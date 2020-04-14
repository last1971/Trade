<?php

return [
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
];
