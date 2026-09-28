<?php

namespace App\Services;

use App\NotifyRoute;

/** Страница «Маршруты уведомлений» — общий механизм таблиц, своей логики нет. */
class NotifyRouteService extends ModelService
{
    public function __construct()
    {
        parent::__construct(NotifyRoute::class);
    }
}
