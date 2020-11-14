<?php


namespace App\Services;


use App\RetailSaleLine;
use App\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use PDO;

class RetailSaleLineService extends ModelService
{
    public function __construct()
    {
        parent::__construct(RetailSaleLine::class);

        $this->addSelect = [
            DB::raw('QUANSHOP * PRICE AS AMOUNT'),
            DB::raw('1 - PRICE / PRICESHOP AS DISCOUNT')
        ];
    }

    public function refund($request, User $user): void
    {
        throw_if(
            !$user->employee,
            new Exception('Не известный кассир')
        );

        $connection = DB::connection('firebird');
        $connection->getPdo()->setAttribute(PDO::ATTR_AUTOCOMMIT, 0);
        $connection->beginTransaction();
        $date = Carbon::parse($request->datetime);
        $nextDate = $date->addSecond();
        $paymentType = 'BLACK';
        $userName = $user->name . ' через electronica.su';

        try {
            $SHOPHEAD = $connection->table('SHOPHEADS')->whereBetween('SALEDATE', [$date, $nextDate])->first();
            if ($SHOPHEAD) {
                $paymentType = $SHOPHEAD['UFN'];
            }
            $noKassa = $paymentType === 'BLACK' ? 1 : 0;
            foreach ($request->selectedIds as $i => $id) {
                $connection->statement(
                    'EXECUTE PROCEDURE DEL_SHOPLOG_TOTAL1(?, ?, 0, ?, ?)',
                    [$userName, $id, $request->selectedQnts[$i], $noKassa]
                );
            }
            $connection
                ->table('SHOPHEADS')
                ->whereBetween('SALEDATE', [$date, $nextDate])
                ->update(['AMOUNT' => $request->amount]);
            //if ($paymentType !== 'BLACK') {
                //
            //}
            $connection->commit();
        } catch (Exception $e) {
            $connection->rollBack();
        }

    }
}
