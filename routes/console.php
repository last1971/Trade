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
    $a = collect([
        'filterAttributes' => ['STATUS', 'DATA'],
        'filterOperators' => ['IN', '>'],
        'filterValues' => [[1, 2], '2017-01-01']
    ]);
    dd($s->index($a)
        //->join('S as invoice', 'invoice.SCODE', '=', 'REALPRICE.SCODE')
        //->whereIn('invoice.STATUS', [1])
        ->first()
    );
})->describe('Display an inspiring quote');
