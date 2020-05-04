<?php

use App\Services\GoodService;
use App\Services\OrderLineService;
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

Artisan::command('test', function (GoodService $s) {
    $k = new OrderLineService();
    $z = $k->index(collect([
        'aggregateAttributes' => ['shopLinesQuantity']
    ]))->find(495283);
    dd($z);

    // DB::connection('firebird')->enableQueryLog();
    $g = $s->index(collect([
        // 'with' => ['shopLinesTransit'],
        'aggregateAttributes' => ['shopLinesTransitQuantity']
    ]))->find(333930);
    $t = $g->orderLinesTransit()->get();
    // Log::debug('update', DB::connection('firebird')->getQueryLog());
    dd($g);
})->describe('Display an inspiring quote');
