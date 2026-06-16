<?php

namespace App\Services\Replenish;

use App\Good;
use Carbon\Carbon;

/**
 * Рекомендация к закупке — оркестровка: данные из ReplenishRepository, расчёт в ReplenishCalculator.
 *
 * report() — детальный отчёт по одному товару.
 * list()   — массовый отчёт по каталогу (двухступенчатая воронка).
 * Оба собирают данные ОДНИМИ И ТЕМИ ЖЕ методами репозитория и считают ОДНИМ compute() — без дублей.
 */
class ReplenishService
{
    /** Окно (дней) для оценки интервала между поставками — шире окна продаж. */
    private const HISTORY_DAYS = 365;

    public function __construct(
        private ReplenishRepository $repo,
        private ReplenishCalculator $calc
    ) {
    }

    /**
     * Детальный отчёт по одному товару.
     *
     * @param int $goodsCode  GOODSCODE товара
     * @param int $leadDays   срок поставки (заказ → приезд), задаётся вручную
     * @param int $periodDays окно анализа продаж (по умолчанию полгода)
     */
    public function report(int $goodsCode, int $leadDays, int $periodDays = 180): array
    {
        $good = Good::with('name')->findOrFail($goodsCode);

        $to = Carbon::now();
        $from = (clone $to)->subDays($periodDays);
        $prihFrom = (clone $to)->subDays(self::HISTORY_DAYS);

        $sales = $this->repo->saleQuantities([$goodsCode], $from, $to)[$goodsCode];
        $prih = $this->repo->prihDates([$goodsCode], $prihFrom)[$goodsCode];
        $stock = $this->repo->stock([$goodsCode])[$goodsCode];
        $inTransit = $this->repo->inTransit([$goodsCode])[$goodsCode];

        $result = $this->calc->compute(
            $sales['opt'], $sales['retail'], $prih, $stock, $inTransit, $leadDays, $periodDays
        );

        return array_merge([
            'good' => [
                'GOODSCODE' => $goodsCode,
                'name' => trim((string)optional($good->name)->NAME),
                'producer' => trim((string)$good->PRODUCER),
                'unit' => $good->unitName,
            ],
            'window' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'days' => $periodDays,
            ],
        ], $result);
    }

    /**
     * Массовый отчёт «что закупить» по каталогу.
     *
     * Этап 1 (период1): продажи > N И остаток < проданного.
     * Этап 2 (период2): детальный расчёт по выжившим, оставляем заказать > 0.
     *
     * @param int $leadDays срок поставки (один на весь прогон)
     * @param int $period1  окно скрининга продаж (дней)
     * @param int $period2  окно расчёта спроса (дней)
     * @param int $minSales порог: продаж за период1 должно быть больше этого числа
     */
    public function list(int $leadDays, int $period1, int $period2, int $minSales): array
    {
        $to = Carbon::now();
        $from1 = (clone $to)->subDays($period1);

        // Этап 1: скрининг по всему каталогу.
        $summary = array_filter(
            $this->repo->salesSummary($from1, $to),
            fn($s) => $s['cnt'] > $minSales
        );
        $screened = count($summary);

        $stockScreen = $this->repo->stock(array_keys($summary));
        $candidates = [];
        foreach ($summary as $g => $s) {
            $avail = $stockScreen[$g]['warehouse'] + $stockScreen[$g]['retail'] - $stockScreen[$g]['reserved'];
            if ($avail < $s['qty']) {
                $candidates[$g] = $s;
            }
        }
        $codes = array_keys($candidates);

        // Этап 2: пакетно собираем данные для детального расчёта по period2.
        $from2 = (clone $to)->subDays($period2);
        $prihFrom = (clone $to)->subDays(self::HISTORY_DAYS);
        $sales = $this->repo->saleQuantities($codes, $from2, $to);
        $prih = $this->repo->prihDates($codes, $prihFrom);
        $stock = $this->repo->stock($codes);
        $inTransit = $this->repo->inTransit($codes);
        $info = $this->goodInfo($codes);

        $rows = [];
        foreach ($codes as $g) {
            $result = $this->calc->compute(
                $sales[$g]['opt'], $sales[$g]['retail'], $prih[$g], $stock[$g], $inTransit[$g], $leadDays, $period2
            );
            if ($result['toOrder'] > 0) {
                $rows[] = [
                    'GOODSCODE' => $g,
                    'name' => $info[$g]['name'] ?? '',
                    'producer' => $info[$g]['producer'] ?? '',
                    'soldPeriod1' => round($candidates[$g]['qty'], 3),
                    'toOrder' => $result['toOrder'],
                ];
            }
        }

        usort($rows, fn($a, $b) => $b['toOrder'] <=> $a['toOrder']);

        return [
            'window' => ['from' => $from1->toDateString(), 'to' => $to->toDateString(), 'days' => $period1],
            'params' => ['leadDays' => $leadDays, 'period2' => $period2, 'minSales' => $minSales],
            'screened' => $screened,         // прошли фильтр по числу продаж
            'candidates' => count($candidates), // + остаток < продаж
            'rows' => $rows,                 // + заказать > 0
        ];
    }

    /**
     * Название и производитель по кодам, пакетно.
     *
     * @param int[] $codes
     * @return array<int, array{name: string, producer: string}>
     */
    private function goodInfo(array $codes): array
    {
        $map = [];
        foreach (array_chunk($codes, 1000) as $chunk) {
            Good::with('name')->whereIn('GOODSCODE', $chunk)->get()
                ->each(function (Good $good) use (&$map) {
                    $map[(int)$good->GOODSCODE] = [
                        'name' => trim((string)optional($good->name)->NAME),
                        'producer' => trim((string)$good->PRODUCER),
                    ];
                });
        }

        return $map;
    }
}
