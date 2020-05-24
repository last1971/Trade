<?php


namespace App\Helpers;


use Carbon\Carbon;

/**
 * Class VatHelper
 * @package App\Helpers
 */
class VatHelper
{
    /**
     * @param Carbon $data
     * @return int
     */
    public static function get(string $data = null)
    {
        if (new Carbon($data) < new Carbon('2019-01-01')) return 18;
        return 20;
    }
}
