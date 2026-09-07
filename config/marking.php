<?php

return [
    // Источник остатков для «Разгребания склада»: sklad (опт, s_s=0) | shop (магазин, s_s=1).
    // На магазинной инсталляции задаётся MARKING_STOCK_MODE=shop в .env.
    'stock_mode' => env('MARKING_STOCK_MODE', 'sklad'),

    // chz-сервис (заказ КМ в СУЗ, статус, коды, этикетки). Фронт напрямую в него не ходит —
    // только через /api/chz/* этого приложения. ИНН — наша организация на этой инсталляции,
    // из participants.json сервиса; пусто — заказ запрещён.
    'chz' => [
        'url' => env('MARKING_CHZ_URL', 'http://192.168.22.35:3010'),
        'api_key' => env('MARKING_CHZ_API_KEY', ''),
        'inn' => env('MARKING_CHZ_INN', ''),
        'timeout' => (int)env('MARKING_CHZ_TIMEOUT', 60),
    ],
];
