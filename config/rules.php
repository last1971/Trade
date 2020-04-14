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
    ]
];
