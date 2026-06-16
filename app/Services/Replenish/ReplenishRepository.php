<?php

namespace App\Services\Replenish;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Единственная точка доступа к Firebird для расчёта закупки.
 *
 * Все методы работают и для одного товара, и для пачки: принимают массив кодов
 * (один товар = массив из одного), возвращают карту, ключ — GOODSCODE. За счёт
 * этого single-отчёт и массовый используют ровно те же запросы — без дублей.
 * IN бьётся чанками (лимит Firebird), raw-алиасы кавычатся (иначе Firebird их аплертит).
 */
class ReplenishRepository
{
    private const CHUNK = 1000;

    private function conn()
    {
        return DB::connection('firebird');
    }

    /** Базовый запрос опт-отгрузок (REALPRICEF ⨝ SF) за окно. */
    private function optBase(Carbon $from, Carbon $to): Builder
    {
        return $this->conn()->table('REALPRICEF')
            ->join('SF', 'SF.SFCODE', '=', 'REALPRICEF.SFCODE')
            ->whereBetween('SF.DATA', [$from->toDateString(), $to->toDateString()]);
    }

    /** Базовый запрос розничных продаж (SHOPLOG, QUANSHOP>0) за окно. */
    private function retailBase(Carbon $from, Carbon $to): Builder
    {
        return $this->conn()->table('SHOPLOG')
            ->where('QUANSHOP', '>', 0)
            ->whereBetween('DATATIME', [
                $from->toDateString() . ' 00:00:00',
                $to->toDateString() . ' 23:59:59',
            ]);
    }

    /**
     * Скрининг: по ВСЕМ товарам за окно — сумма проданного и число продаж (опт+розница).
     *
     * @return array<int, array{qty: float, cnt: int}>
     */
    public function salesSummary(Carbon $from, Carbon $to): array
    {
        $map = [];

        $opt = $this->optBase($from, $to)
            ->groupBy('REALPRICEF.GOODSCODE')
            ->get([
                'REALPRICEF.GOODSCODE as g',
                DB::raw('sum(REALPRICEF.QUAN) as "qty"'),
                DB::raw('count(*) as "cnt"'),
            ]);
        foreach ($opt as $r) {
            $map[(int)$r->g] = ['qty' => (float)$r->qty, 'cnt' => (int)$r->cnt];
        }

        $retail = $this->retailBase($from, $to)
            ->groupBy('GOODSCODE')
            ->get([
                'GOODSCODE as g',
                DB::raw('sum(QUANSHOP) as "qty"'),
                DB::raw('count(*) as "cnt"'),
            ]);
        foreach ($retail as $r) {
            $map[(int)$r->g] ??= ['qty' => 0.0, 'cnt' => 0];
            $map[(int)$r->g]['qty'] += (float)$r->qty;
            $map[(int)$r->g]['cnt'] += (int)$r->cnt;
        }

        return $map;
    }

    /**
     * Разовые продажи по каждому коду за окно (для сглаживания спроса).
     * Опт и розница раздельно — single-отчёту нужна разбивка.
     *
     * @param int[] $codes
     * @return array<int, array{opt: float[], retail: float[]}>
     */
    public function saleQuantities(array $codes, Carbon $from, Carbon $to): array
    {
        $map = array_fill_keys($codes, ['opt' => [], 'retail' => []]);

        foreach (array_chunk($codes, self::CHUNK) as $chunk) {
            $opt = $this->optBase($from, $to)
                ->whereIn('REALPRICEF.GOODSCODE', $chunk)
                ->get(['REALPRICEF.GOODSCODE as g', 'REALPRICEF.QUAN as q']);
            foreach ($opt as $r) {
                $map[(int)$r->g]['opt'][] = (float)$r->q;
            }

            $retail = $this->retailBase($from, $to)
                ->whereIn('GOODSCODE', $chunk)
                ->get(['GOODSCODE as g', 'QUANSHOP as q']);
            foreach ($retail as $r) {
                $map[(int)$r->g]['retail'][] = (float)$r->q;
            }
        }

        return $map;
    }

    /**
     * Даты приходов (ZAKAZ_MASTER.DATA_PRIH) по каждому коду от даты $from — уникальные, по возрастанию.
     *
     * @param int[] $codes
     * @return array<int, string[]>
     */
    public function prihDates(array $codes, Carbon $from): array
    {
        $map = array_fill_keys($codes, []);

        foreach (array_chunk($codes, self::CHUNK) as $chunk) {
            $rows = $this->conn()->table('ZAKAZ_DETAIL')
                ->join('ZAKAZ_MASTER', 'ZAKAZ_MASTER.ID', '=', 'ZAKAZ_DETAIL.MASTER_ID')
                ->whereIn('ZAKAZ_DETAIL.GOODSCODE', $chunk)
                ->whereNotNull('ZAKAZ_MASTER.DATA_PRIH')
                ->where('ZAKAZ_MASTER.DATA_PRIH', '>=', $from->toDateString())
                ->get(['ZAKAZ_DETAIL.GOODSCODE as g', 'ZAKAZ_MASTER.DATA_PRIH as d']);
            foreach ($rows as $r) {
                $map[(int)$r->g][] = Carbon::parse($r->d)->toDateString();
            }
        }

        foreach ($map as $g => $dates) {
            $map[$g] = collect($dates)->unique()->sort()->values()->all();
        }

        return $map;
    }

    /**
     * Остаток по каждому коду: склад (SKLAD), магазин (SHOPSKLAD), резерв (RESERVEDPOS).
     *
     * @param int[] $codes
     * @return array<int, array{warehouse: float, retail: float, reserved: float}>
     */
    public function stock(array $codes): array
    {
        $map = array_fill_keys($codes, ['warehouse' => 0.0, 'retail' => 0.0, 'reserved' => 0.0]);

        foreach (array_chunk($codes, self::CHUNK) as $chunk) {
            $this->sumInto($map, 'warehouse', 'SKLAD', 'QUAN', $chunk);
            $this->sumInto($map, 'retail', 'SHOPSKLAD', 'QUAN', $chunk);
            $this->sumInto($map, 'reserved', 'RESERVEDPOS', 'QUANSHOP + QUANSKLAD', $chunk);
        }

        return $map;
    }

    /**
     * В пути (заказано у поставщика, статусы 2,3) по каждому коду.
     *
     * @param int[] $codes
     * @return array<int, float>
     */
    public function inTransit(array $codes): array
    {
        $map = array_fill_keys($codes, 0.0);

        foreach (array_chunk($codes, self::CHUNK) as $chunk) {
            $rows = $this->conn()->table('ZAKAZ_DETAIL')
                ->join('ZAKAZ_MASTER', 'ZAKAZ_MASTER.ID', '=', 'ZAKAZ_DETAIL.MASTER_ID')
                ->whereIn('ZAKAZ_DETAIL.GOODSCODE', $chunk)
                ->whereIn('ZAKAZ_MASTER.STATUS', [2, 3])
                ->groupBy('ZAKAZ_DETAIL.GOODSCODE')
                ->get(['ZAKAZ_DETAIL.GOODSCODE as g', DB::raw('sum(ZAKAZ_DETAIL.QUAN) as "s"')]);
            foreach ($rows as $r) {
                $map[(int)$r->g] = (float)$r->s;
            }
        }

        return $map;
    }

    /**
     * Прибавить в карту остатков сгруппированную по GOODSCODE сумму выражения.
     *
     * @param array<int, array<string, float>> $map
     * @param int[] $chunk
     */
    private function sumInto(array &$map, string $key, string $table, string $expr, array $chunk): void
    {
        $rows = $this->conn()->table($table)
            ->whereIn('GOODSCODE', $chunk)
            ->groupBy('GOODSCODE')
            ->get(['GOODSCODE as g', DB::raw("sum($expr) as \"s\"")]);
        foreach ($rows as $r) {
            if (isset($map[(int)$r->g])) {
                $map[(int)$r->g][$key] = (float)$r->s;
            }
        }
    }
}
