<?php

namespace App\Services\Marking;

use App\GoodClassif;
use Illuminate\Support\Facades\DB;

/**
 * Запись вердикта классификации товара (подлежит/не подлежит + ТНВЭД/ОКПД2).
 * Единственное место записи — зовётся из API (GoodGtinController::classify)
 * и из команды авто-классификации (tnved:classify).
 *
 * Вердикт живёт на строке IS_PRIMARY=1 (одна на товар, GTIN может быть NULL);
 * «не подлежит» гасит MARK_REQUIRED на всех строках товара, потому что
 * «подлежит?» проверяется как EXISTS(строка с MARK_REQUIRED=1).
 */
class GoodClassifyService
{
    /**
     * @throws \Exception при откате транзакции — зовущий решает, как показать.
     */
    public function setVerdict(
        int $goodscode,
        int $markRequired,
        ?string $tnved = null,
        ?string $okpd2 = null,
        ?string $prim = null
    ): GoodClassif {
        $connection = DB::connection('firebird');
        $connection->getPdo()->setAttribute(\PDO::ATTR_AUTOCOMMIT, 0);
        $connection->beginTransaction();
        try {
            $primary = GoodClassif::query()
                ->where('GOODSCODE', $goodscode)
                ->where('IS_PRIMARY', 1)
                ->first();
            if ($primary) {
                $primary->update([
                    'MARK_REQUIRED' => $markRequired,
                    'TNVED' => $tnved ?: $primary->TNVED,
                    'OKPD2' => $okpd2 ?: $primary->OKPD2,
                    'PRIM' => $prim,
                    'UPDATED_AT' => now(),
                ]);
            } else {
                $primary = GoodClassif::query()->create([
                    'GOODSCODE' => $goodscode,
                    'IS_PRIMARY' => 1,
                    'MARK_REQUIRED' => $markRequired,
                    'TNVED' => $tnved,
                    'OKPD2' => $okpd2,
                    'PRIM' => $prim,
                    'UPDATED_AT' => now(),
                ]);
            }
            GoodClassif::query()
                ->where('GOODSCODE', $goodscode)
                ->where('ID', '<>', $primary->ID)
                ->where('IS_PRIMARY', 1)
                ->update(['IS_PRIMARY' => 0, 'UPDATED_AT' => now()]);
            if ($markRequired === 0) {
                GoodClassif::query()
                    ->where('GOODSCODE', $goodscode)
                    ->where('MARK_REQUIRED', 1)
                    ->update(['MARK_REQUIRED' => 0, 'UPDATED_AT' => now()]);
            }
            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();
            throw $e;
        } finally {
            $connection->getPdo()->setAttribute(\PDO::ATTR_AUTOCOMMIT, 1);
        }

        return $primary;
    }
}
