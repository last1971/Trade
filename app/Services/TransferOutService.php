<?php


namespace App\Services;


use App\TransferOut;
use Illuminate\Database\Eloquent\Builder;

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
}
