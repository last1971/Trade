<?php

namespace App\Providers;

use Illuminate\Database\Query\Builder;
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
        //
        Builder::macro('invoiceLinesSum', function () {
            $this->select(DB::raw('sum(SUMMAP)'));
        });
        Builder::macro('invoiceLinesCount', function () {
            $this->select(DB::raw('count(SCODE)'));
        });
    }
}
