<?php

namespace App\Services\Marking;

use App\TnvedSuggestion;
use Illuminate\Support\Facades\Cache;

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

    public function __construct(
        private StockClassifService $stock,
        private AutoClassifyService $auto
    ) {
    }

    /**
     * Пересобрать предложения: берём limit товаров «не проверяли», подбираем,
     * непринятые старые предложения заменяем. Возвращает число предложений.
     */
    public function refresh(int $limit, int $threshold): int
    {
        // Товары, уже лежащие в подборе, ИИ повторно не трогаем — добираем только
        // следующую пачку свежих «не проверяли», которых ещё нет в таблице.
        $exclude = TnvedSuggestion::pluck('goodscode')->all();

        $count = 0;
        foreach ($this->stock->uncheckedCodes($limit, $exclude) as $code) {
            $r = $this->auto->classifyOne($code, $threshold);
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

        Cache::put(self::CACHE_UPDATED_AT, now()->toDateTimeString());

        return $count;
    }
}
