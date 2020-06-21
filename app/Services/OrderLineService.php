<?php


namespace App\Services;


use App\OrderLine;
use Illuminate\Database\Eloquent\Builder;

class OrderLineService extends ModelService
{
    // protected $addSelect = ['order.DATA_PRIH as orderComeDate'];

    protected $whereAttributes = [
        'inWay' => '(select COALESCE(sum(SKLADIN.QUAN), 0) from SKLADIN where ZAKAZ_DETAIL.ID = SKLADIN.ZAKAZ_DETAIL_ID)
                     + (select COALESCE(sum(SHOPIN.QUAN), 0) from SHOPIN where ZAKAZ_DETAIL.ID = SHOPIN.ZAKAZ_DETAIL_ID)
                     < ZAKAZ_DETAIL.QUAN'
    ];

    public function __construct()
    {
        parent::__construct(OrderLine::class);

        $this
            ->query
            ->join('ZAKAZ_MASTER as order', 'order.ID', '=', 'ZAKAZ_DETAIL.MASTER_ID');

        $this->aggregateAttributes = [
            'shopLinesQuantity' => ['shopLines' => function (Builder $query) {
                $query->shopLinesQuantity();
            }],
            'storeLinesQuantity' => ['storeLines' => function (Builder $query) {
                $query->storeLinesQuantity();
            }],
        ];

        $this->aliases['category.CATEGORY'] = function (Builder $query) {
            $query
                ->join(
                    'CATEGORY as category', 'category.CATEGORYCODE', '=', 'good.CATEGORYCODE'
                );
        };
        $this->aliases['name.NAME'] = function (Builder $query) {
            $query
                ->join('NAME as name', 'name.NAMECODE', '=', 'good.NAMECODE');
        };

        $this->floatAttributes = ['PRICE', 'SUMMAP'];
    }
}
