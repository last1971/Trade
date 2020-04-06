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

    //$pdo = new \PDO('firebird:dbname=192.168.14.200:/home/db/base.fdb;charset=utf-8;dialect=3', 'SYSDBA', '641767');
    //$s1 = $pdo->prepare('SELECT * FROM REALPRICE ROWS 1');
    //$s1->execute();
    //$r = $s1->fetch();
    //dd($r);
    $s = new InvoiceService();
    $q = collect([
        'with' => ['employee', 'buyer'],
        // 'selectAttributes' => ['SCODE', 'NS', 'POKUPATCODE', 'STAFF_ID'],
        'aggregateAttributes' => ['invoiceLinesCount', 'invoiceLinesSum', 'transferOutLinesSum', 'cashFlowsSum'],
        //'filterAttributes' => ['DATA'],
        //'filterOperators' => ['>'],
        //'filterValues' => ['01.01.2018'],
        //'sortBy' => ['DATA', 'invoiceLinesSum'],
        //'sortDesc' => ['desc', 'asc'],
    ]);
    dd($s->index($q)->first());
    //dd($s->index($q)->select('S.*', \Illuminate\Support\Facades\DB::raw('
    //    (SELECT sum(REALPRICEF.SUMMAP) as otgr from REALPRICEF, REALPRICE WHERE REALPRICEF.REALPRICECODE=REALPRICE.REALPRICECODE
    //    AND REALPRICE.SCODE=S.SCODE)
    //'))->first());

})->describe('Display an inspiring quote');
