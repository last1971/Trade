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
            'reservesQuantity' => ['reserves' => function (Builder $query) {
                $query->reservesQuantity();
            }],
            'orderLinesTransitQuantity' => ['orderLinesTransit' => function (Builder $query) {
                $query->orderLinesQuantity();
            }],
            'shopLinesTransitQuantity' => ['shopLinesTransit' => function (Builder $query) {
                $query->shopLinesQuantity();
            }],
            'storeLinesTransitQuantity' => ['storeLinesTransit' => function (Builder $query) {
                $query->storeLinesQuantity();
            }]
        ];
    }

}
