<?php

namespace App\Console\Commands;

use App\Services\Marking\TnvedSuggestService;
use Illuminate\Console\Command;

/**
 * Воркер параллельной пачки tnved:suggest: подбирает свой кусок кодов и печатает
 * число сложенных предложений (родитель суммирует по stdout). Руками не запускать.
 */
class TnvedSuggestWorker extends Command
{
    /**
     * @var string
     */
    protected $signature = 'tnved:suggest-worker
        {--codes= : коды товаров через запятую}
        {--confidence=80 : порог уверенности}';

    /**
     * @var string
     */
    protected $description = 'Воркер пачки авто-подбора ТН ВЭД (внутренний, зовёт tnved:suggest)';

    public function handle(TnvedSuggestService $service): int
    {
        $codes = array_values(array_filter(array_map('intval', explode(',', (string) $this->option('codes')))));
        $count = $service->processCodes($codes, (int) $this->option('confidence'));
        $this->line((string) $count);

        return 0;
    }
}
