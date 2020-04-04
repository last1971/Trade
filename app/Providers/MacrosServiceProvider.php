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

        // For Invoice
        Builder::macro('invoiceLinesSum', function () {
            $this->select(DB::raw('sum(SUMMAP)'));
        });
        Builder::macro('invoiceLinesCount', function () {
            $this->select(DB::raw('count(SCODE)'));
        });
    }
}
