<?php

use App\InvoiceLine;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->describe('Display an inspiring quote');

Artisan::command('test', function () {
    //DB::connection('firebird')->raw('update "REALPRICE" set "PRICE" = 2.45 where "REALPRICECODE" = 480705');
    $inv = InvoiceLine::find(480705);
    $inv->update(['PRICE' => DB::raw('2.45')]);
})->describe('Display an inspiring quote');
