<?php

namespace App\Providers;

use App\PaymentOrder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

class MacrosServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //For All
        Builder::macro('aggregateAttributes', function (array $aggregateAttributes = null, array $helper = null) {
            return $this->when($aggregateAttributes, function (Builder $query, array $aggregateAttributes) use ($helper) {
                $withCount = [];
                foreach ($aggregateAttributes as $aggregateAttribute) {
                    $data = $helper[$aggregateAttribute];
                    $withCount[key($data) . ' as ' . $aggregateAttribute] = current($data);
                }
                $query->withCount($withCount);
            });
        });

        // For CashFlow
        Builder::macro('cashFlowsSum', function () {
            $this->select(DB::raw('COALESCE(sum(MONEYSCHET), 0)'));
        });

        // For InvoiceLines
        Builder::macro('invoiceLinesSum', function () {
            $this->select(DB::raw('COALESCE(sum(REALPRICE.SUMMAP), 0)'));
        });
        Builder::macro('invoiceLinesCount', function () {
            $this->select(DB::raw('count(SCODE)'));
        });
        Builder::macro('invoiceLinesQuantity', function () {
            $this->select(DB::raw('sum(QUAN)'))
                ->join('S', 'S.SCODE', '=', 'REALPRICE.SCODE');
        });

        // For OrderLines
        Builder::macro('orderLinesSum', function () {
            $this->select(DB::raw('COALESCE(sum(ZAKAZ_DETAIL.SUMMAP), 0)'));
        });
        Builder::macro('orderLinesCount', function () {
            $this->select(DB::raw('count(ZAKAZ_DETAIL.ID)'));
        });
        Builder::macro('orderLinesQuantity', function () {
            $this->select(DB::raw('sum(ZAKAZ_DETAIL.QUAN)'))
                ->join('ZAKAZ_MASTER', 'ZAKAZ_MASTER.ID', '=', 'ZAKAZ_DETAIL.MASTER_ID');
        });

        // For PickUps
        Builder::macro('pickUpsQuantity', function () {
            $this->select(DB::raw('COALESCE(sum(PODBPOS.QUANSHOP + PODBPOS.QUANSKLAD), 0)'))
                ->join('S', 'S.SCODE', '=', 'REALPRICE.SCODE');
        });

        // For Reserves
        Builder::macro('reservesQuantity', function () {
            $this->select(DB::raw('COALESCE(sum(RESERVEDPOS.QUANSHOP + RESERVEDPOS.QUANSKLAD), 0)'));
        });
        Builder::macro('reservesQuantityTransit', function () {
            $this->select(DB::raw('COALESCE(sum(RESERVEDPOS.QUANSHOP + RESERVEDPOS.QUANSKLAD), 0)'))
                ->join('S', 'S.SCODE', '=', 'REALPRICE.SCODE');
        });


        // For RetailOrderLines
        Builder::macro('retailOrderLinesRemainingQuantity', function () {
            $this->select(
                DB::raw('COALESCE(
                    sum(ROZN_DETAIL.QUAN-ROZN_DETAIL.QUAN_RES-ROZN_DETAIL.QUAN_PODB-ROZN_DETAIL.QUAN_SALED), 0)'
                )
            );
        });

        // For ShopLines
        Builder::macro('shopLinesQuantity', function () {
            $this->select(DB::raw('COALESCE(sum(SHOPIN.QUAN), 0)'))
                ->join('ZAKAZ_MASTER', 'ZAKAZ_MASTER.ID', '=', 'ZAKAZ_DETAIL.MASTER_ID');
        });

        // For StoreLines
        Builder::macro('storeLinesQuantity', function () {
            $this->select(DB::raw('COALESCE(sum(SKLADIN.QUAN), 0)'))
                ->join('ZAKAZ_MASTER', 'ZAKAZ_MASTER.ID', '=', 'ZAKAZ_DETAIL.MASTER_ID');
        });

        // For TransferLines
        Builder::macro('transferOutLinesSum', function () {
            $this->select(DB::raw('COALESCE(sum(REALPRICEF.SUMMAP), 0)'));
        });
        Builder::macro('transferOutLinesQuantity', function () {
            $this->select(DB::raw('COALESCE(sum(REALPRICEF.QUAN), 0)'));
        });
        Builder::macro('transferOutLinesCount', function () {
            $this->select(DB::raw('count(REALPRICEFCODE)'));
        });
        // For PaymentOrders
        Builder::macro('paid', function () {
            $this->select(DB::raw('COALESCE(count(amount), 0)'));
        });


        if (!Collection::hasMacro('sortByMulti')) {
            /**
             * An extension of the {@see Collection::sortBy()} method that allows for sorting against as many different
             * keys. Uses a combination of {@see Collection::sortBy()} and {@see Collection::groupBy()} to achieve this.
             *
             * @param array $keys An associative array that uses the key to sort by (which accepts dot separated values,
             *                    as {@see Collection::sortBy()} would) and the value is the order (either ASC or DESC)
             */
            Collection::macro('sortByMulti', function (array $keys) {
                $keys = array_map(function ($key, $sort) {
                    return ['key' => $key, 'sort' => $sort];
                }, array_keys($keys), $keys);

                $sortBy = function (Collection $collection, $currentIndex) use ($keys, &$sortBy) {
                    if ($currentIndex >= count($keys)) {
                        return $collection;
                    }

                    $key = $keys[$currentIndex]['key'];
                    $sort = $keys[$currentIndex]['sort'];
                    $sortFunc = $sort === 'DESC' ? 'sortByDesc' : 'sortBy';

                    $sorted_collection = $collection->$sortFunc($key)->values();

                    $values = $sorted_collection->pluck($key)->unique()->values();

                    $ret = collect();

                    foreach ($values as $value) {
                        $current = $sorted_collection->filter(function ($val) use ($key, $value) {
                            return $val[$key] == $value;
                        });
                        $ret = $ret->merge($sortBy($current, $currentIndex + 1));
                    }
                    return $ret;
                };
                return $sortBy($this, 0);
            });
        }

        $aggregateAttributes = [
            'paid' => PaymentOrder::query()
                ->whereColumn('payment_id', 'payments.id')
                ->selectRaw('coalesce(sum(amount), 0)')
        ];

        //for request()
        Builder::macro('smartWhere', function($attribute, $operator, $value) {
            if ($operator === 'IN') {
                $parseValue = is_string($value) ? json_decode($value) : $value;
                $parseValue = is_array($parseValue) ? $parseValue : [$parseValue];
                $this->whereIn($attribute, $parseValue);
            } else {
                $this->where($attribute, $operator, $value);
            }
        });

        Builder::macro('requestBuilder', function () use ($aggregateAttributes) {
            $request = request()->query();
            if (isset($request['with'])) {
                $this->with($request['with']);
            }
            if (isset($request['sortBy'])) {
                foreach ($request['sortBy'] as $index => $orderBy) {
                    $this->orderBy($orderBy, $request['sortDesc'][$index] === 'true' ? 'desc' : 'asc');
                }
            }
            if (isset($request['filterAttributes'])) {
                foreach ($request['filterAttributes'] as $index => $attribute) {
                    $value = $request['filterOperators'][$index] === 'LIKE'
                        ? '%' . $request['filterValues'][$index] . '%'
                        : $request['filterValues'][$index];
                    $value = array_key_exists($value, $aggregateAttributes)
                        ? function($query) use ($value, $aggregateAttributes) {
                            $query->select([$value => $aggregateAttributes[$value]]);
                        }
                        : $value;
                    $whereHas = explode('.', $attribute);
                    if (count($whereHas) > 1) {
                        $this->whereHas($whereHas[0], function (Builder $query)
                        use ($whereHas, $request, $index, $value) {
                            $query->smartWhere(
                                $whereHas[1],
                                $request['filterOperators'][$index],
                                $value,
                            );
                        });
                    } else {
                        $whereAttribute = array_key_exists($attribute, $aggregateAttributes)
                            ? $aggregateAttributes[$attribute]
                            : $attribute;
                        $this->smartWhere(
                            $whereAttribute,
                            $request['filterOperators'][$index],
                            $value
                        );
                    }
                }
            }
            if (isset($request['aggregateAttributes'])) {
                $requestAggregateAttributes = is_array($request['aggregateAttributes'])
                    ? $request['aggregateAttributes']
                    : [$request['aggregateAttributes']];
                foreach ($requestAggregateAttributes as $aggregateAttribute) {
                    $this->addSelect([$aggregateAttribute => $aggregateAttributes[$aggregateAttribute]]);
                }
            }
            return $this;
        });

    }
}
