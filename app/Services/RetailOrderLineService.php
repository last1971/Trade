<?php


namespace App\Services;


use App\RetailOrderLine;
use Illuminate\Database\Eloquent\Builder;

class RetailOrderLineService extends ModelService
{
    public function __construct()
    {
        parent::__construct(RetailOrderLine::class);

        $this->whereAttributes['retailOrderLinesRemainingQuantity'] =
            'ROZN_DETAIL.QUAN-ROZN_DETAIL.QUAN_RES-ROZN_DETAIL.QUAN_PODB-ROZN_DETAIL.QUAN_SALED > 0';

        $this->whereAttributes['retailOrderLinesNotClosed'] = 'ROZN_DETAIL.QUAN > ROZN_DETAIL.QUAN_SALED';

        $this->dateAttributes = ['retailOrder.DATA'];

        $this->aliases['retailOrder.DATA'] = function (Builder $query) {
            $query->join(
                'ROZN_MASTER as retailOrder',
                'retailOrder.ID',
                '=',
                'ROZN_DETAIL.MASTER_ID'
            );
        };

        $this->aliases['retailOrder.POKUPATCODE'] = function (Builder $query) {
            $query->join(
                'ROZN_MASTER as retailOrder',
                'retailOrder.ID',
                '=',
                'ROZN_DETAIL.MASTER_ID'
            );
        };
    }
}
