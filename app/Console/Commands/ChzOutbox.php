<?php

namespace App\Console\Commands;

use App\Services\Marking\ChzOutboxService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Воркер очереди отправки в Честный знак: раз в минуту из планировщика.
 * Логики здесь нет — она в ChzOutboxService.
 */
class ChzOutbox extends Command
{
    protected $signature = 'chz:outbox';

    protected $description = 'Отправка пачек кодов в Честный знак и проверка их результата';

    public function handle(ChzOutboxService $service)
    {
        if (!$service->enabled()) {
            return 0;
        }
        try {
            foreach ($service->tick() as $line) {
                $this->info($line);
                $this->log('info', 'chz:outbox ' . $line);
            }
            return 0;
        } catch (Throwable $e) {
            $this->error($e->getMessage());
            $this->log('error', 'chz:outbox failed: ' . $e->getMessage());
            return 1;
        }
    }

    private function log(string $level, string $message): void
    {
        try {
            Log::$level($message);
        } catch (Throwable $e) {
            // логгер недоступен — воркеру это не мешает
        }
    }
}
