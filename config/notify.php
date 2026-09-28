<?php

// Уведомления: куда слать — в таблице NOTIFY_ROUTE (страница «Маршруты уведомлений»),
// здесь только секреты и константы транспорта. Не делать config:cache — см. laravel_config_cache_breaks_env.
return [
    'matrix' => [
        // Homeserver и токен бота @bot:elcopro.ru. Токен — свой на инсталляцию
        // (device notify-trade-opt / notify-trade-shop): перелогин одного не убивает другого.
        'homeserver' => env('MATRIX_HOMESERVER'),
        'token' => env('MATRIX_ACCESS_TOKEN'),
        'timeout' => (int)env('MATRIX_TIMEOUT', 5),
        // Больше в Element не читается: хвост уходит письмом по той же теме.
        'max_length' => 4000,
    ],

    // Неотправленное в Matrix ждёт в Redis и повторяется раз в минуту (artisan notify:retry).
    'retry' => [
        'ttl_hours' => 24,
        // Через сколько минут молчания Matrix слать одно письмо «Matrix недоступен» (тема DEV).
        'down_alert_minutes' => 10,
    ],
];
