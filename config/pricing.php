<?php

use App\Services\Pricing\Compel;
use App\Services\Pricing\DataBase;
use App\Services\Pricing\ElcoPro;
use App\Services\Pricing\Ruichi;

return [
    'Compel' => [
        'cacheTimes' => 3600,
        'sellerId' => 857,
        'basicDeliveryTime' => 7,
        'class' => Compel::class,
        'ereg' => false,
    ],
    'Dan' => [
        'cacheTimes' => 86400,
        'sellerId' => 1068,
        'basicDeliveryTime' => 4,
        'class' => DataBase::class,
        'ereg' => true,
    ],
    'ElcoPro' => [
        'cacheTimes' => 120,
        'sellerId' => 0,
        'basicDeliveryTime' => 0,
        'class' => ElcoPro::class,
        'ereg' => true,
    ],
    'Ruichi' => [
        'cacheTimes' => 7200,
        'sellerId' => (int) env('RUICHI_SELLER_ID', 2559),
        'basicDeliveryTime' => 11,
        'class' => Ruichi::class,
        'ereg' => true,
    ]
];
