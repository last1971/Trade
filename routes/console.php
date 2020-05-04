<?php

use App\Invoice;
use App\Services\TransferOutLineService;
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

Artisan::command('test', function (TransferOutLineService $s) {
    $inv = Invoice::find(61266);
    DB::connection('firebird')->update('UPDATE S SET DATA = \'29.04.2020\' WHERE SCODE = 61266');
})->describe('Display an inspiring quote');
