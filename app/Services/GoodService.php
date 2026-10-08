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
        // Поиск товара по коду маркировки ЧЗ (сканером в поле поиска)
        $this->aliases['markCodes.KI'] = function (Builder $query) {
            $query->join('MARKCODES as markCodes', 'markCodes.GOODSCODE', '=', 'GOODS.GOODSCODE');
        };
    }

    public function index($request)
    {
        $attributes = $request->get('filterAttributes') ?? [];
        $index = array_search('goodNames.NAME', $attributes);
        if ($index === false) {
            return parent::index($request);
        }

        $operators = $request->get('filterOperators');
        $values = $request->get('filterValues');
        $operator = $operators[$index];
        $value = is_string($values[$index]) ? GoodName::normalize($values[$index]) : $values[$index];

        // Имя ищем через EXISTS, а не JOIN: у товара в GOOD_NAMES бывает несколько имён,
        // JOIN размножал строки — дубли в выдаче и завышенный счётчик страниц.
        $this->query->whereExists(function ($query) use ($operator, $value) {
            $query->selectRaw('1')
                ->from('GOOD_NAMES as goodNames')
                ->whereColumn('goodNames.GOODSCODE', 'GOODS.GOODSCODE');
            if ($operator === 'IN') {
                $query->whereIn('goodNames.NAME', $value);
            } elseif ($operator === 'CONTAIN') {
                $query->where('goodNames.NAME', 'CONTAINING', $value);
            } else {
                $query->where('goodNames.NAME', $operator, $value);
            }
        });

        unset($attributes[$index], $operators[$index], $values[$index]);
        $filters = [
            'filterAttributes' => array_values($attributes),
            'filterOperators' => array_values($operators),
            'filterValues' => array_values($values),
        ];
        if ($request instanceof Collection) {
            foreach ($filters as $key => $filter) {
                $request->put($key, $filter);
            }
        } else {
            $request->merge($filters);
        }
        return parent::index($request);
    }
}
