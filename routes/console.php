<?php

use App\Services\InvoiceService;
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
    $s = new InvoiceService();
    $q = collect([
        'with' => ['employee', 'buyer'],
        'selectAttributes' => ['SCODE', 'NS', 'POKUPATCODE', 'STAFF_ID'],
        'aggregateAttributes' => ['invoiceLinesCount', 'invoiceLinesSum'],
        //'filterAttributes' => ['DATA'],
        //'filterOperators' => ['>'],
        //'filterValues' => ['01.01.2018'],
        //'sortBy' => ['DATA', 'invoiceLinesSum'],
        //'sortDesc' => ['desc', 'asc'],
    ]);
    dd($s->index($q)->find(57192));

})->describe('Display an inspiring quote');
