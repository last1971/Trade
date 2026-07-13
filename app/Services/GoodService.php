<?php


namespace App\Services;


use App\Good;
use App\GoodName;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class GoodService extends ModelService
{
    public function __construct()
    {
        parent::__construct(Good::class);

        $this->aggregateAttributes = [
            'invoiceLinesQuantity' => ['invoiceLines' => function (Builder $query) {
                $query->invoiceLinesQuantity();
            }],
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
            'storeLinesQuantity' => ['storeLines' => function (Builder $query) {
                $query->storeLinesQuantityWithoutMaster();
            }],
            'storeLinesTransitQuantity' => ['storeLinesTransit' => function (Builder $query) {
                $query->storeLinesQuantity();
            }],
            'transferOutLinesQuantity' => ['transferOutLines' => function (Builder $query) {
                $query->transferOutLinesQuantity();
            }],
        ];

        $this->aliases['retailStore.QUAN'] = function (Builder $query) {
            $query
                ->join(
                    'SHOPSKLAD as retailStore',
                    'retailStore.GOODSCODE',
                    '=',
                    'GOODS.GOODSCODE'
                );
        };
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

    public function index($request)
    {
        $index = $request->get('filterAttributes')
            ? array_search('goodNames.NAME', $request->get('filterAttributes'))
            : false;
        if ($index !== false && is_string($request->get('filterValues')[$index])) {
            $values = $request->get('filterValues');
            $values[$index] = GoodName::normalize($values[$index]);
            $request instanceof Collection
                ? $request->put('filterValues', $values)
                : $request->merge(['filterValues' => $values]);
        }
        return parent::index($request);
    }
}
