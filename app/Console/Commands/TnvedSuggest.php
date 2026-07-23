<?php

namespace App\Console\Commands;

use App\Services\Marking\TnvedSuggestService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Фоновая пачка авто-подбора ТН ВЭД для страницы-ревью: наполняет
 * tnved_suggestions предложениями по товарам «не проверяли». Долгая (ИИ по
 * каждому товару) — запускается из веба в фоне, фронт поллит статус.
 */
class TnvedSuggest extends Command
{
    /**
     * @var string
     */
    protected $signature = 'tnved:suggest
        {--limit=50 : сколько товаров «не проверяли» подобрать}
        {--confidence=80 : порог уверенности; ниже — в ревью не попадает}';

    /**
     * @var string
     */
    protected $description = 'Фоновый авто-подбор ТН ВЭД для страницы-ревью (наполняет tnved_suggestions)';

    public function handle(TnvedSuggestService $service): int
    {
        // Параллельный запуск бессмыслен. TTL 3600: пачка из ИИ-вызовов может идти
        // долго; при жёсткой смерти флаг обязан протухнуть сам.
        if (!Cache::add(TnvedSuggestService::CACHE_RUNNING, now()->toDateTimeString(), 3600)) {
            $this->warn('tnved:suggest уже выполняется');
            return 1;
        }

        $limit = max(1, (int) $this->option('limit'));
        $threshold = (int) $this->option('confidence');

        try {
            $this->log('info', "tnved:suggest start (limit={$limit}, confidence={$threshold})");
            $count = $service->refresh($limit, $threshold);
            $this->log('info', "tnved:suggest done, предложений: {$count}");
            $this->info("Готово, предложений: {$count}");
            return 0;
        } catch (\Throwable $e) {
            $this->log('error', 'tnved:suggest failed: ' . $e->getMessage());
            $this->error($e->getMessage());
            return 1;
        } finally {
            Cache::forget(TnvedSuggestService::CACHE_RUNNING);
        }
    }

    private function log(string $level, string $message): void
    {
        try {
            Log::$level($message);
        } catch (\Throwable $e) {
            // Логи на проде бывают недоступны (права storage/logs) — молча продолжаем.
        }
    }
}
