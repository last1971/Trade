<?php

namespace App\Console\Commands;

use App\Services\Notify\MatrixSender;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/** Повтор неотправленного в Matrix: раз в минуту из планировщика. Логика в MatrixSender. */
class NotifyRetry extends Command
{
    protected $signature = 'notify:retry';

    protected $description = 'Повторная отправка сообщений Matrix из очереди notify:retry';

    public function handle(MatrixSender $sender)
    {
        try {
            $result = $sender->retry();
            if ($result['sent'] || $result['dropped']) {
                $line = sprintf('notify:retry отправлено %d, выброшено %d, осталось %d', $result['sent'], $result['dropped'], $result['left']);
                $this->info($line);
                Log::info($line);
            }
            return 0;
        } catch (Throwable $e) {
            $this->error($e->getMessage());
            Log::error('notify:retry failed: ' . $e->getMessage());
            return 1;
        }
    }
}
