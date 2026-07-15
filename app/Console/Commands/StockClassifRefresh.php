<?php

namespace App\Console\Commands;

use App\Services\Marking\StockClassifService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class StockClassifRefresh extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stock:classif';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Пересчёт снапшота стоимости остатка склада для страницы «Разгребание склада»';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(StockClassifService $service)
    {
        // Тяжёлый запрос идёт ~2.5 мин — параллельный запуск бессмыслен и вреден.
        if (!Cache::add(StockClassifService::CACHE_RUNNING, now()->toDateTimeString(), 1800)) {
            $this->warn('stock:classif уже выполняется');
            return 1;
        }
        Log::info('stock:classif start');
        try {
            $count = $service->refresh();
            Log::info("stock:classif done, goods: {$count}");
            $this->info("Готово, товаров с остатком: {$count}");
            return 0;
        } catch (\Throwable $e) {
            Log::error('stock:classif failed: ' . $e->getMessage());
            $this->error($e->getMessage());
            return 1;
        } finally {
            Cache::forget(StockClassifService::CACHE_RUNNING);
        }
    }
}
