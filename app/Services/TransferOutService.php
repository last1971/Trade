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
    }
}
