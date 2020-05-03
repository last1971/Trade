<?php

use App\Services\TransferOutLineService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Carbon;
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
    $query = $s->index(collect([
        'with' => ['transferOut.invoice', 'name', 'invoiceLine'],
        'filterAttributes' => ['transferOut.POKUPATCODE', 'transferOut.DATA'],
        'filterOperators' => ['=', 'BETWEENDATE'],
        'filterValues' => [3092, ['2020-04-28', Carbon::create('2020-04-28')->addDay()]],
    ]));
    dd($query->first());
})->describe('Display an inspiring quote');
