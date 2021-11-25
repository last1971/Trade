<?php

use App\Services\Pricing\ChipDip;
use App\Services\Pricing\Compel;
use App\Services\Pricing\DataBase;
use App\Services\Pricing\DigiKey;
use App\Services\Pricing\ElcoPro;
use App\Services\Pricing\Electronica;
use App\Services\Pricing\Elitan;
use App\Services\Pricing\PME;
use App\Services\Pricing\Promelec;
use App\Services\Pricing\Ruichi;

return [
    'ChipDip' => [
        'cacheTimes' => 18000,
        'sellerId' => 1232,
        'basicDeliveryTime' => 1,
        'class' => ChipDip::class,
        'ereg' => false,
    ],
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
    'DigiKey' => [
        'cacheTimes' => 64800,
        'sellerId' => (int) env('DIGIKEY_SELLER_ID', 2660),
        'basicDeliveryTime' => 24,
        'class' => DigiKey::class,
        'ereg' => true,
    ],
    'ElcoPro' => [
        'cacheTimes' => 1800,
        'sellerId' => 0,
        'basicDeliveryTime' => 0,
        'class' => ElcoPro::class,
        'ereg' => true,
    ],
    'Electronica' => [
        'cacheTimes' => 1800,
        'sellerId' => (int) env('ELECTRONICA_SELLER_ID', 2683),
        'basicDeliveryTime' => 1,
        'class' => Electronica::class,
        'ereg' => false,
    ],
    'Elitan' => [
        'cacheTimes' => 86400,
        'sellerId' => (int) env('ELITAN_SELLER_ID', 1399),
        'basicDeliveryTime' => 7,
        'class' => Elitan::class,
        'ereg' => false,
    ],
    'Mars' => [
        'cacheTimes' => 86400,
        'sellerId' => 1579,
        'basicDeliveryTime' => 12,
        'class' => DataBase::class,
        'ereg' => true,
    ],
    'PME' => [
        'cacheTimes' => 43200,
        'sellerId' => (int) env('PME_SELLER_ID', 2109),
        'basicDeliveryTime' => 18,
        'class' => PME::class,
        'ereg' => false,
    ],
    'Promelec' => [
        'cacheTimes' => 7200,
        'sellerId' => (int) env('PROM_SELLER_ID', 860),
        'basicDeliveryTime' => 8,
        'class' => Promelec::class,
        'ereg' => true,
    ],
    'Rct' => [
        'cacheTimes' => 86400,
        'sellerId' => (int) env('RCT_SELLER_ID', 2341),
        'basicDeliveryTime' => 14,
        'class' => DataBase::class,
        'ereg' => true,
    ],
    'Ruichi' => [
        'cacheTimes' => 21600,
        'sellerId' => (int) env('RUICHI_SELLER_ID', 2559),
        'basicDeliveryTime' =>11,
        'class' => Ruichi::class,
        'ereg' => true,
    ],
    'maxCacheTimes' => 172800,
];
