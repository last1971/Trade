<?php


namespace App\Services;


use App\TransferOut;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class TransferOutService extends ModelService
{
    public function __construct()
    {
        parent::__construct(TransferOut::class);

        $this->aggregateAttributes = [
            'transferOutLinesSum' => ['transferOutLines' => function (Builder $query) {
                $query->transferOutLinesSum();
            }],
            'transferOutLinesCount' => ['transferOutLines' => function (Builder $query) {
                $query->transferOutLinesCount();
            }],
        ];

        $this->dateAttributes = ['DATA'];

        $this->aliases['buyer.SHORTNAME'] = function (Builder $query) {
            $query->join('POKUPAT as buyer', 'buyer.POKUPATCODE', '=', 'SF.POKUPATCODE');
        };
        $this->aliases['employee.FULLNAME'] = function (Builder $query) {
            $query->join('STAFF as employee', 'employee.ID', '=', 'SF.STAFF_ID');
        };
        $this->aliases['firm.FIRMNAME'] = function (Builder $query) {
            $query->join('FIRMS as firm', 'firm.FIRM_ID', '=', 'SF.FIRM_ID');
        };
    }

    public function index($request)
    {
        $res = parent::index($request);
        if ($request->user() && !$request->user()->userBuyers->isEmpty()) {
            $userBuyers = Str::replaceLast(
                ',', '',
                $request->user()->userBuyers->reduce(
                    function ($carry, $buyer) {
                        return $carry . $buyer->buyer_id . ',';
                    },
                    ''
                )
            );
            $res->whereRaw('SF.POKUPATCODE IN (' . $userBuyers . ')');
        }
        return $res;
    }
}
