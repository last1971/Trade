<?php

namespace App\Services\Marking;

/**
 * Режим инсталляции для партионных расчётов маркировки: склад (s_s=0) или магазин (s_s=1).
 * Единственное место, где config marking.stock_mode переводится в S_S для процедур Firebird.
 */
final class StockMode
{
    public const SKLAD = 0;
    public const SHOP = 1;

    public static function ss(): int
    {
        return config('marking.stock_mode') === 'shop' ? self::SHOP : self::SKLAD;
    }
}
