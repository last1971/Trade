<?php


namespace App\Services;


use App\Good;
use Illuminate\Database\Eloquent\Builder;

class GoodService extends ModelService
{
    public function __construct()
    {
        parent::__construct(Good::class);

        $this->aggregateAttributes = [
            'invoiceLinesQuantityTransit' => ['invoiceLinesTransit' => function (Builder $query) {
                $query->invoiceLinesQuantity();
            }],
            'orderLinesTransitQuantity' => ['orderLinesTransit' => function (Builder $query) {
                $query->orderLinesQuantity();
            }],
            'pickUpsTransitQuantity' => ['pickUpsTransit' => function (Builder $query) {
                $query->pickUpsQuantity();
            }],
            'retailOrderLinesNeedQuantity' => ['retailOrderLinesTransit' => function (Builder $query) {
                $query->retailOrderLinesRemainingQuantity();
            }],
            'reservesQuantity' => ['reserves' => function (Builder $query) {
                $query->reservesQuantity();
            }],
            'reservesQuantityTransit' => ['reservesTransit' => function (Builder $query) {
                $query->reservesQuantityTransit();
            }],
            'shopLinesTransitQuantity' => ['shopLinesTransit' => function (Builder $query) {
                $query->shopLinesQuantity();
            }],
            'storeLinesTransitQuantity' => ['storeLinesTransit' => function (Builder $query) {
                $query->storeLinesQuantity();
            }]
        ];

        $this->aliases['name.NAME'] = function (Builder $query) {
            $query
                ->join('NAME as name', 'name.NAMECODE', '=', 'GOODS.NAMECODE');
        };
        $this->aliases['category.CATEGORY'] = function (Builder $query) {
            $query->join(
                'CATEGORY as category', 'category.CATEGORYCODE', '=', 'GOODS.CATEGORYCODE'
            );
        };
        $this->aliases['goodNames.NAME'] = function (Builder $query) {
            $query
                ->join(
                    'GOOD_NAMES as goodNames', 'goodNames.GOODSCODE', '=', 'GOODS.GOODSCODE'
                );
        };
    }
}
