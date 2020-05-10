<?php

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
    // $r = DB::connection('firebird')->query('SELECT * FROM S')->select
    // DB::connection('firebird')->enableQueryLog();
    $g = $s->index(collect([
        //'with' => ['retailPrice', 'orderStep', 'retailStore', 'warehouse', 'name', 'category'],
        'aggregateAttributes' => [
            'pickUpsQuantity', 'reservesQuantity'
        ],
        'filterAttributes' => ['notPickUp', 'invoice.STATUS'],
        'filterOperators' => ['=', "IN"],
        'filterValues' => [1, [2, 3]]
    ]))->first();
    dd($g);
    $t = $g->orderLinesTransit()->get();
    // Log::debug('update', DB::connection('firebird')->getQueryLog());
    dd(
        $g->retailPrice->getAttributes(),
        $g->orderStep->getAttributes(),
        $g->retailStore->getAttributes(),
        $g->warehouse->getAttributes(),
        $g->name->getAttributes(),
        $g->category->getAttributes(),
        $g->getAttributes()
    );
})->describe('Display an inspiring quote');
