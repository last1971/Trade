<?php

use App\Services\ForDeleteService;
use App\Services\InvoiceLineService;
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

Artisan::command('test', function (InvoiceLineService $s) {

});

Artisan::command('test', function () {
    dd(is_numeric(mb_ereg_replace("\s|,", '', "1 000 000,00")));
})->describe('Display an inspiring quote');
