<?php

namespace App\Services\Marking;

use Illuminate\Support\Facades\DB;

/**
 * «Непокрыто» по партиям товара — справка для выпуска КМ: один код на партию
 * (количественный КИ кроет непокрытую часть партии), партий N → N кодов.
 * Источник истины — процедура MARK_UNCOVERED_PARCELS (патч 48), та же, по которой
 * MARK_UNCOVERED_STOCK считает итог для «Разгребания склада».
 */
class UncoveredParcelsService
{
    public function forGood(int $goodscode): array
    {
        $ss = StockMode::ss();
        $rows = DB::connection('firebird')->select(
            'select pmid, incode, np, pdate, p_quan, p_blocked, p_codes, p_free from MARK_UNCOVERED_PARCELS(?, ?)',
            [$goodscode, $ss]
        );
        $parcels = array_map(fn($row) => [
            'pmid' => intval($row->PMID),
            'incode' => intval($row->INCODE),
            'np' => trim((string)$row->NP),
            'date' => $row->PDATE ? substr((string)$row->PDATE, 0, 10) : null,
            'quan' => intval($row->P_QUAN),
            'blocked' => intval($row->P_BLOCKED),
            'codes' => intval($row->P_CODES),
            'free' => intval($row->P_FREE),
        ], $rows);
        return [
            'mode' => $ss === StockMode::SHOP ? 'shop' : 'sklad',
            'total' => array_sum(array_column($parcels, 'free')),
            'parcels' => $parcels,
        ];
    }
}
