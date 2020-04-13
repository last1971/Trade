<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Builder;
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

        // For OrderLines
        Builder::macro('orderLinesSum', function () {
            $this->select(DB::raw('COALESCE(sum(ZAKAZ_DETAIL.SUMMAP), 0)'));
        });
        Builder::macro('orderLinesCount', function () {
            $this->select(DB::raw('count(ZAKAZ_DETAIL.ID)'));
        });

        // For PickUps
        Builder::macro('pickUpsQuantity', function () {
            $this->select(DB::raw('COALESCE(sum(PODBPOS.QUANSHOP + PODBPOS.QUANSKLAD), 0)'));
        });

        // For Reserves
        Builder::macro('reservesQuantity', function () {
            $this->select(DB::raw('COALESCE(sum(RESERVEDPOS.QUANSHOP + RESERVEDPOS.QUANSKLAD), 0)'));
        });

        // For ShopLines
        Builder::macro('shopLinesQuantity', function () {
            $this->select(DB::raw('COALESCE(sum(SHOPIN.QUAN), 0)'));
        });

        // For StoreLines
        Builder::macro('storeLinesQuantity', function () {
            $this->select(DB::raw('COALESCE(sum(SKLADIN.QUAN), 0)'));
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
    }
}
