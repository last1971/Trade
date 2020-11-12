<?php


namespace App\Services;


use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GoodsListService
{
    public function isLocalTransaction()
    {
        if (DB::connection('firebird')->transactionLevel() === 0) {
            DB::connection('firebird')->getPdo()->setAttribute(\PDO::ATTR_AUTOCOMMIT, 0);
            DB::connection('firebird')->beginTransaction();
            return true;
        }
        return false;
    }

    /**
     * @param int $GOODSCODE
     * @param int $quantity
     * @param float $price
     * @param int|null $POKUPATCODE
     * @param int|null $ROZN_DETAI_ID
     * @param User $user
     * @param Carbon|null $date
     * @throws \Throwable
     */
    public function saleLine(
        int $GOODSCODE,
        int $quantity,
        float $price,
        ?int $POKUPATCODE,
        ?int $ROZN_DETAI_ID,
        User $user,
        Carbon $date = null
    ): void
    {
        $connection = DB::connection('firebird');
        $isLocalTransaction = $this->isLocalTransaction();
        $date = $date ?? Carbon::now();
        $userName = $user->name . ' через electronica.su';

        try {
            $connection
                ->statement(
                    'EXECUTE PROCEDURE WRITESHOPLOG6(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    [
                        $userName,
                        $date,
                        $GOODSCODE,
                        0,
                        $quantity,
                        $price,
                        null,
                        0,
                        $user->employeeId,
                        $POKUPATCODE,
                        $ROZN_DETAI_ID,
                        null
                    ]
                );

            $shopLog = $connection
                ->select(
                    'SELECT SHOPLOGCODE FROM SHOPLOG
                     WHERE GOODSCODE=? AND datatime=? AND username=? AND quansklad=? AND quanshop=?',
                    [$GOODSCODE, $date, $userName, 0, $quantity]
                );

            throw_if(
                count($shopLog) !== 1, new \Exception('Ошибка продажи товара c кодом ' . $GOODSCODE)
            );

            $connection
                ->statement(
                    'EXECUTE PROCEDURE NEWSHOPOUT2(?, ?, ?, ?, ?)',
                    [$GOODSCODE, $quantity, $date, $price, $shopLog[0]->SHOPLOGCODE]
                );

            if ($isLocalTransaction) {
                $connection->commit();
            }
        } catch (\Exception $e) {
            if ($isLocalTransaction) {
                $connection->rollBack();
            }
            throw $e;
        }
    }

    public function sale(array $lines, User $user, ?int $buyerId): void
    {
        $connection = DB::connection('firebird');
        $isLocalTransaction = $this->isLocalTransaction();
        $date = Carbon::now();

        try {
            foreach ($lines as $line) {
                $this->saleLine(
                    $line['GOODSCODE'],
                    $line['quantity'],
                    $line['price'],
                    $buyerId,
                    $line['retailOrderLineId'],
                    $user,
                    $date
                );
            }

            if ($isLocalTransaction) {
                $connection->commit();
            }
        } catch (\Exception $e) {
            if ($isLocalTransaction) {
                $connection->rollBack();
            }
            throw $e;
        }
    }

}
