<?php

namespace App\Services\Replenish;

use Carbon\Carbon;

/**
 * Чистый расчёт рекомендации к закупке — без БД.
 * Единственное место формулы; и single-отчёт, и массовый зовут compute().
 *
 *   заказать = спрос/день × (срок_поставки + интервал_поставок) − свободно − в_пути
 *
 * Спрос/день сглажен: весь объём / период, но каждая разовая продажа обрезана сверху
 * по 90-му перцентилю — одна крупная отгрузка не задирает темп, обычный объём считается
 * целиком (в отличие от медианы, которая занулила бы рваный спрос).
 */
class ReplenishCalculator
{
    /**
     * @param float[] $optQuantities    разовые опт-продажи за период
     * @param float[] $retailQuantities разовые розничные продажи за период
     * @param string[] $prihDates       даты приходов (для интервала поставок)
     * @param array{warehouse: float, retail: float, reserved: float} $stock
     * @return array
     */
    public function compute(
        array $optQuantities,
        array $retailQuantities,
        array $prihDates,
        array $stock,
        float $inTransit,
        int $leadDays,
        int $periodDays
    ): array {
        $quantities = array_merge($optQuantities, $retailQuantities);
        $demandPerDay = $this->demandPerDay($quantities, $periodDays);
        $intervalDays = $this->interval($prihDates);

        $available = $stock['warehouse'] + $stock['retail'] - $stock['reserved'];
        $coverDays = $leadDays + ($intervalDays ?? $leadDays);
        $target = $demandPerDay * $coverDays;
        $toOrder = max(0, (int)ceil($target - $available - $inTransit));

        $optSum = array_sum($optQuantities);
        $retailSum = array_sum($retailQuantities);
        $totalSold = round($optSum + $retailSum, 3);

        return [
            'sales' => [
                'opt' => round($optSum, 3),
                'retail' => round($retailSum, 3),
                'total' => $totalSold,
                'count' => count($quantities),
                'meanPerDay' => $periodDays > 0 ? round($totalSold / $periodDays, 3) : 0,
                'smoothedPerDay' => round($demandPerDay, 3),
            ],
            'supply' => [
                'intervalDays' => $intervalDays,
                'leadDays' => $leadDays,
                'coverDays' => $coverDays,
            ],
            'stock' => [
                'warehouse' => round($stock['warehouse'], 3),
                'retailStore' => round($stock['retail'], 3),
                'reserved' => round($stock['reserved'], 3),
                'available' => round($available, 3),
                'inTransit' => round($inTransit, 3),
            ],
            'target' => round($target, 3),
            'toOrder' => $toOrder,
        ];
    }

    /** Сглаженный спрос/день: объём с обрезкой выбросов / период. */
    private function demandPerDay(array $quantities, int $periodDays): float
    {
        if (!$quantities || $periodDays <= 0) {
            return 0.0;
        }

        $cap = $this->percentile($quantities, 90);
        $sum = array_sum(array_map(fn($q) => min($q, $cap), $quantities));

        return $sum / $periodDays;
    }

    /** Интервал между поставками: медиана разрывов (в днях) между датами прихода. null, если поставок < 2. */
    private function interval(array $dates): ?int
    {
        $dates = array_values(array_unique($dates));
        sort($dates);
        if (count($dates) < 2) {
            return null;
        }

        $gaps = [];
        for ($i = 1; $i < count($dates); $i++) {
            $gaps[] = Carbon::parse($dates[$i - 1])->diffInDays(Carbon::parse($dates[$i]));
        }

        return (int)round($this->median($gaps));
    }

    /** Перцентиль (0..100) по списку чисел, линейная интерполяция. */
    private function percentile(array $values, float $p): float
    {
        sort($values);
        $n = count($values);
        if ($n <= 1) {
            return (float)($values[0] ?? 0);
        }

        $rank = ($p / 100) * ($n - 1);
        $low = (int)floor($rank);
        $high = (int)ceil($rank);
        if ($low === $high) {
            return (float)$values[$low];
        }

        return $values[$low] + ($rank - $low) * ($values[$high] - $values[$low]);
    }

    /** Медиана списка чисел. */
    private function median(array $values): float
    {
        if (!$values) {
            return 0.0;
        }
        sort($values);
        $n = count($values);
        $mid = intdiv($n, 2);

        return $n % 2
            ? (float)$values[$mid]
            : ($values[$mid - 1] + $values[$mid]) / 2;
    }
}
