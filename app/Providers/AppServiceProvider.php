<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Jenssegers\Date\Date;
use URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // For MariaDB
        Schema::defaultStringLength(191);
        // hhtps on prod
        if (env('APP_ENV') !== 'local') {
            URL::forceScheme('https');
        }
        // locale
        setlocale(LC_ALL, 'ru_RU.utf8');
        Carbon::setLocale(config('app.locale'));
        Date::setLocale('ru');
    }
}
