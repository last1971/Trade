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
     * Категории (с подкатегориями), реально присутствующие на складе — для
     * фильтра на странице. Тянется фронтом один раз.
     */
    public function categories(StockClassifService $service): array
    {
        return $service->categories();
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
            // Вывод не в /dev/null: www-data не может писать в storage/logs, и без
            // этого файла смерть веб-запуска не оставляет вообще никаких следов.
            exec('php ' . base_path('artisan') . ' stock:classif >> /tmp/stock-classif-web.log 2>&1 &');
        }
        return ['running' => true];
    }
}
