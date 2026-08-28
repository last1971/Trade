<?php

namespace App\Services\Marking;

use App\TnvedSuggestion;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

/**
 * Фоновая пачка авто-подбора: по товарам «не проверяли» гоняет classifyOne и
 * складывает готовые к применению предложения (status=ok) в tnved_suggestions
 * для страницы-ревью. ИИ дёргается только здесь — применение потом без него.
 *
 * Своей бизнес-логики не держит: выборка — StockClassifService::uncheckedCodes,
 * подбор — AutoClassifyService::classifyOne.
 */
class TnvedSuggestService
{
    public const CACHE_RUNNING = 'tnved-suggest:running';
    public const CACHE_UPDATED_AT = 'tnved-suggest:updated_at';

    /** Сколько воркеров-процессов гонят пачку параллельно. */
    public const DEFAULT_PARALLEL = 8;

    public function __construct(
        private StockClassifService $stock,
        private AutoClassifyService $auto
    ) {
    }

    /**
     * Пересобрать предложения: берём limit товаров «не проверяли», подбираем,
     * непринятые старые предложения заменяем. Возвращает число предложений.
     */
    public function refresh(int $limit, int $threshold, int $parallel = self::DEFAULT_PARALLEL): int
    {
        // Товары, уже лежащие в подборе, ИИ повторно не трогаем — добираем только
        // следующую пачку свежих «не проверяли», которых ещё нет в таблице.
        $exclude = TnvedSuggestion::pluck('goodscode')->all();
        $codes = $this->stock->uncheckedCodes($limit, $exclude);

        $count = $parallel > 1 && count($codes) > 1
            ? $this->runParallel($codes, $threshold, $parallel)
            : $this->processCodes($codes, $threshold);

        Cache::put(self::CACHE_UPDATED_AT, now()->toDateTimeString());

        return $count;
    }

    /**
     * Подобрать по списку кодов последовательно и сложить предложения.
     * Зовут воркер tnved:suggest-worker и однопоточный режим refresh().
     *
     * @param array<int,int> $codes
     */
    public function processCodes(array $codes, int $threshold): int
    {
        $count = 0;
        foreach ($codes as $code) {
            try {
                $r = $this->auto->classifyOne((int) $code, $threshold);
            } catch (\Throwable $e) {
                // Один кривой ответ ИИ не должен убивать всю пачку — пропускаем товар.
                $this->log("tnved:suggest товар {$code} пропущен: " . $e->getMessage());
                continue;
            }
            // В ревью кладём только готовые к применению (есть код и уверенность ≥ порога);
            // остальные остаются «не проверяли» — их закрывают руками галочками.
            if ($r['status'] !== 'ok') {
                continue;
            }
            TnvedSuggestion::updateOrCreate(
                ['goodscode' => $code],
                [
                    'name' => $r['name'],
                    'tnved' => $r['tnved'],
                    'tnved_name' => $r['tnved_name'],
                    'mark_required' => $r['mark_required'],
                    'okpd2' => $r['okpd2'],
                    'confidence' => $r['confidence'],
                    'model' => $r['model'],
                    'reason' => $r['reason'],
                    'status' => 'pending',
                ]
            );
            $count++;
        }

        return $count;
    }

    /**
     * Пачка на N воркеров-процессов: каждый товар — 2-4 последовательных вызова ИИ
     * (до ~40 с с эскалацией на Opus), в одном потоке 50 товаров = полчаса.
     * Режем коды на $parallel кусков и гоним их отдельными artisan-процессами.
     *
     * @param array<int,int> $codes
     */
    private function runParallel(array $codes, int $threshold, int $parallel): int
    {
        $chunks = array_chunk($codes, (int) ceil(count($codes) / $parallel));
        $procs = [];
        foreach ($chunks as $chunk) {
            $p = new Process([
                PHP_BINARY, base_path('artisan'), 'tnved:suggest-worker',
                '--codes=' . implode(',', $chunk),
                '--confidence=' . $threshold,
            ]);
            $p->setTimeout(3600);
            $p->start();
            $procs[] = $p;
        }

        $count = 0;
        foreach ($procs as $p) {
            $p->wait();
            $out = trim($p->getOutput());
            if ($p->isSuccessful() && is_numeric($out)) {
                $count += (int) $out;
            } else {
                $this->log('tnved:suggest воркер упал: ' . $out . ' ' . trim($p->getErrorOutput()));
            }
        }

        return $count;
    }

    private function log(string $message): void
    {
        try {
            Log::warning($message);
        } catch (\Throwable $e) {
            // Логи на проде бывают недоступны (права storage/logs) — молча продолжаем.
        }
    }
}
