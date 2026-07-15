<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Marking\StockClassifService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class StockClassifController extends Controller
{
    /**
     * Страница списка «Разгребание склада» из снапшота
     * (сортировка по стоимости остатка, фильтры-«проблемы»).
     */
    public function index(Request $request, StockClassifService $service): array
    {
        return $service->list($request);
    }

    /**
     * Статус пересчёта — фронт поллит после нажатия «Обновить данные».
     */
    public function status(): array
    {
        return [
            'running' => (bool)Cache::get(StockClassifService::CACHE_RUNNING),
            'updated_at' => Cache::get(StockClassifService::CACHE_UPDATED_AT),
        ];
    }

    /**
     * Запуск пересчёта в фоне (~2.5 мин) — HTTP-запрос не ждёт его окончания.
     * Повторный запуск при уже идущем пересчёте отсекает Cache::add в команде.
     */
    public function refresh(): array
    {
        if (!Cache::get(StockClassifService::CACHE_RUNNING)) {
            exec('php ' . base_path('artisan') . ' stock:classif > /dev/null 2>&1 &');
        }
        return ['running' => true];
    }
}
