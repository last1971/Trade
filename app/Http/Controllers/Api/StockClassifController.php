<?php

namespace App\Http\Controllers\Api;

use App\Exports\StockClassifExport;
use App\Imports\StockClassifImport;
use App\Http\Controllers\Controller;
use App\Services\Marking\StockClassifService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Excel;

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
     * Excel того же списка по тем же фильтрам — целиком, без нарезки на страницы.
     */
    public function export(Request $request, Excel $excel, StockClassifService $service)
    {
        // Весь склад (~5 тыс. строк) собирается ~15 с — запас против 30 с max_execution_time
        set_time_limit(120);
        $rows = $service->list($request, true)['data'];

        return $excel->download(new StockClassifExport($rows, $service->categories()), 'Разгребание склада.xlsx');
    }

    /**
     * Загрузка разметки из заполненного Excel (тот же формат, что выгрузка).
     */
    public function import(Request $request, StockClassifImport $import): array
    {
        $request->validate(['file' => 'required|file|mimes:xlsx']);
        set_time_limit(120);
        try {
            return $import->apply($request->file('file'));
        } catch (\InvalidArgumentException $e) {
            abort(422, $e->getMessage());
        }
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
