<?php


namespace App\Helpers;


use Carbon\Carbon;

/**
 * Class VatHelper
 * @package App\Helpers
 */
class VatHelper
{
    private const FIRM_ELECTRONIKA = 31;  // НДС 5%

    /**
     * @param string|null $data
     * @param int|null $firmId
     * @return int
     */
    public static function get(string $data = null, int $firmId = null)
    {
        // Электроника - всегда 5%
        if ($firmId === self::FIRM_ELECTRONIKA) return 5;

        if (new Carbon($data) < new Carbon('2019-01-01')) return 18;
        if (new Carbon($data) < new Carbon('2026-01-01')) return 20;
        return 22;
    }
}
