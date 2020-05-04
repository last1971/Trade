<?php


namespace App\Services;


use App\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class OrderService extends ModelService
{
    public function __construct()
    {
        parent::__construct(Order::class);

        $this->aggregateAttributes = [
            'cashFlowsSum' => ['cashFlows' => function (Builder $query) {
                $query->cashFlowsSum();
            }],
            'orderLinesCount' => ['orderLines' => function (Builder $query) {
                $query->orderLinesCount();
            }],
            'orderLinesSum' => ['orderLines' => function (Builder $query) {
                $query->orderLinesSum();
            }],
        ];

        $this->dateAttributes = ['DATA', 'INVOICE_DATA'];

        $this->aliases['seller.NAMEPOST'] = function (Builder $query) {
            $query->join(
                'WHEREISPOST as seller',
                'seller.WHEREISPOSTCODE',
                '=',
                'ZAKAZ_MASTER.WHEREISPOSTCODE'
            );
        };
        $this->aliases['employee.FULLNAME'] = function (Builder $query) {
            $query->join('STAFF as employee', 'employee.ID', '=', 'ZAKAZ_MASTER.STAFF_ID');
        };

        $this->addSelect = [
            DB::raw(
                'coalesce((select sum(trueprih.summap) from ZAKAZ_DETAIL, SKLADIN, pr_meta, trueprih
                    where ZAKAZ_DETAIL.MASTER_ID=zakaz_master.id and SKLADIN.zakaz_detail_id=zakaz_detail.id
                    and pr_meta.skladincode=skladin.skladincode and trueprih.trueprihcodecode=pr_meta.trueprihcode), 0)
                    + coalesce((select sum(trueprih.summap) from ZAKAZ_DETAIL, SHOPIN, pr_meta, trueprih
                    where ZAKAZ_DETAIL.MASTER_ID=zakaz_master.id and SHOPIN.zakaz_detail_id=zakaz_detail.id
                    and pr_meta.shopincode=SHOPIN.shopincode and trueprih.trueprihcodecode=pr_meta.trueprihcode), 0)
                    as inSum'
            )
        ];

    }
}
