<?php


namespace App\Services;


use App\Exceptions\ApiException;
use App\Http\Requests\RefundRequest;
use App\RetailSaleLine;
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

    /**
     * @param RefundRequest $request
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Throwable
     */
    public function refund(RefundRequest $request): void
    {
        $user = $request->user();
        throw_if(
            !$user->employee,
            new Exception('Не известный кассир')
        );

        $connection = DB::connection('firebird');
        $connection->getPdo()->setAttribute(PDO::ATTR_AUTOCOMMIT, 0);
        $connection->beginTransaction();
        $date = Carbon::parse($request->datatime);
        $nextDate = Carbon::parse($request->datatime)->addSecond();
        $userName = $user->name . ' через www';
        $SHOPHEAD = $connection->table('SHOPHEADS')->whereBetween('SALEDATE', [$date, $nextDate])->first();
        throw_if(!$SHOPHEAD, new ApiException('Продажа не найдена'));

        $urn = '';

        try {
            $paymentType = $SHOPHEAD->UFN;
            $oldAmount = $SHOPHEAD->AMOUNT;
            $saleDate = Carbon::parse($SHOPHEAD->SALEDATE);
            $noKassa = $paymentType === 'BLACK' ? 1 : 0;

            if (strlen($paymentType) === 12) {
                $eft = new EFTPOSService();
                $now = Carbon::now();
                if ($saleDate->day === $now->day && $saleDate->month === $now->month) {
                    $reversal = $eft->reversalOfSale($paymentType, $oldAmount, $oldAmount - $request->amount);
                    $urn = $reversal['urn'];
                } else {
                    $credit = $eft->credit($request->amount);
                    $urn = $credit['urn'];
                }
            }


            foreach ($request->selectedIds as $i => $id) {
                $connection->statement(
                    'EXECUTE PROCEDURE DEL_SHOPLOG_TOTAL1(?, ?, 0, ?, ?)',
                    [$userName, $id, $request->selectedQnts[$i], $noKassa]
                );
            }
            $connection
                ->table('SHOPHEADS')
                ->whereBetween('SALEDATE', [$date, $nextDate])
                ->update(['AMOUNT' => $oldAmount - $request->amount]);
            if ($paymentType !== 'BLACK') {
                $service = new AtolService();
                $service->operator = $user;
                $paymentType = $paymentType === 'CASH' ? 'cash' : 'electronically';
                $service->receipt($request->items,'sellReturn', $paymentType);
            }
            $connection->commit();
        } catch (Exception $e) {
            if ($urn !== '') {
                $eft = new EFTPOSService();
                $eft->void($urn);
            }
            $connection->rollBack();
            throw $e;
        }

    }
}
