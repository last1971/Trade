<?php

use App\Invoice;
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
    //$res = \Illuminate\Support\Facades\DB::connection('firebird')->select("SELECT * from \"S\" where \"DATA\" > '01.01.2020'");
    //$res = \Illuminate\Support\Facades\DB::connection('firebird')->table('S')->where('DATA', '>', '\'01.01.2020\'')->get();
    //->whereRaw('"DATA" > CAST(? as DATE)', '2020-01-01')->get(); //  select("SELECT * from \"S\" where \"DATA\" > '01.01.2020'");
    //dd($res);
    $s = new InvoiceService();
    $q = collect([
        //'with' => ['employee', 'buyer'],
        //'selectAttributes' => ['SCODE', 'NS', 'POKUPATCODE', 'STAFF_ID'],
        // 'agregateAttributes' => ['invoiceLinesCount', 'invoiceLinesSum'],
        'filterAttributes' => ['DATA'],
        'filterOperators' => ['>'],
        'filterValues' => ['01.01.2018'],
        //'sortBy' => ['DATA', 'invoiceLinesSum'],
        //'sortDesc' => ['desc', 'asc'],
    ]);
    dd($s->index($q)->get());

    $invoiceLinesSum = function ($query) {
        $query->invoiceLinesSum();
    };
    $query = Invoice::query();
    $query->with(['employee']);
    //$query->select('SCODE');
    //$query->withCount([
    ///    'invoiceLines',
    //    'invoiceLines as summap' => $invoiceLinesSum
    //]);
    $query->orderBy('DATA', 'desc');
    //$query->orderBy(
    //    'summap', 'desc'
    //);
    //->has('summap', '>', 100)
    //$query->whereHas('invoiceLines', ${'invoiceLinesSum'}, '>=', 1000000);
    $res = $query->first();
    // $res = \Illuminate\Support\Facades\DB::connection('firebird')->
    // select(\Illuminate\Support\Facades\DB::raw($res));
    //
    dd($res->items()[0]);
})->describe('Display an inspiring quote');
