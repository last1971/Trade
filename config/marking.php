<?php

return [
    // Источник остатков для «Разгребания склада»: sklad (опт, s_s=0) | shop (магазин, s_s=1).
    // На магазинной инсталляции задаётся MARKING_STOCK_MODE=shop в .env.
    'stock_mode' => env('MARKING_STOCK_MODE', 'sklad'),
];
