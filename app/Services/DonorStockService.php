<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Подобранное в счетах-донорах маркетплейса — единственное место, где это считается.
 *
 * Донор — счёт покупателя-маркетплейса в статусе 1 (сформирован), где товар подобран.
 * Берётся только подобранное количество, своей стороной инсталляции: опт — склад
 * (QUANSKLAD), розница — магазин (QUANSHOP). Страница и xlsx берут данные отсюда.
 */
class DonorStockService
{
    /** Ключи маркетплейсов, которые можно выбрать (из config/app.php). */
    public static function marketplaces(): array
    {
        return array_keys(config('app.marketplace_buyers'));
    }

    /**
     * Сводно по товару: сколько подобрано и в скольких счетах.
     *
     * @param string $marketplace ключ из marketplaces()
     * @return array{rows: array, total: array{goods: int, quantity: float}}
     */
    public function list(string $marketplace): array
    {
        $buyer = config('app.marketplace_buyers.' . $marketplace);
        abort_unless($buyer, 422, 'Не задан код покупателя «' . $marketplace . '» в .env');
        $quantity = 'PODBPOS.' . (config('app.is_shop') ? 'QUANSHOP' : 'QUANSKLAD');

        $rows = DB::connection('firebird')->table('PODBPOS')
            ->join('S', 'S.SCODE', '=', 'PODBPOS.SCODE')
            ->join('GOODS', 'GOODS.GOODSCODE', '=', 'PODBPOS.GOODSCODE')
            ->join('NAME', 'NAME.NAMECODE', '=', 'GOODS.NAMECODE')
            ->where('S.POKUPATCODE', $buyer)
            ->where('S.STATUS', 1)
            ->where($quantity, '>', 0)
            ->groupBy('PODBPOS.GOODSCODE', 'NAME.NAME')
            ->orderBy('NAME.NAME')
            ->get([
                'PODBPOS.GOODSCODE',
                'NAME.NAME as name',
                DB::raw("sum($quantity) as \"quantity\""),
                DB::raw('count(distinct S.SCODE) as "invoices"'),
            ])
            ->map(fn($r) => [
                'GOODSCODE' => (int)$r->GOODSCODE,
                'name' => $r->name,
                'quantity' => (float)$r->quantity,
                'invoices' => (int)$r->invoices,
            ])
            ->all();

        return [
            'rows' => $rows,
            'total' => [
                'goods' => count($rows),
                'quantity' => array_sum(array_column($rows, 'quantity')),
            ],
        ];
    }
}
