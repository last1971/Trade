<?php

use App\Services\OrderService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Role;

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
    $s = Role::all();
    dd($s);
    $s = new OrderService();
    $q = collect([
        'with' => ['employee', 'seller'],
        // 'selectAttributes' => ['SCODE', 'NS', 'POKUPATCODE', 'STAFF_ID'],
        'aggregateAttributes' => ['orderLinesCount'],
        //'filterAttributes' => ['inWay'],
        //'filterOperators' => [null],
        //'filterValues' => [null],
        'sortBy' => ['ID'],
        'sortDesc' => [true],
    ]);
    dd($s->index($q)
        // ->whereRaw('(select COALESCE(sum(SKLADIN.QUAN), 0) from SKLADIN where ZAKAZ_DETAIL.ID = SKLADIN.ZAKAZ_DETAIL_ID) + (select COALESCE(sum(SHOPIN.QUAN), 0) from SHOPIN where ZAKAZ_DETAIL.ID = SHOPIN.ZAKAZ_DETAIL_ID) < ZAKAZ_DETAIL.QUAN')
        ->first());
    //dd($s->index($q)->select('S.*', \Illuminate\Support\Facades\DB::raw('
    //    (SELECT sum(REALPRICEF.SUMMAP) as otgr from REALPRICEF, REALPRICE WHERE REALPRICEF.REALPRICECODE=REALPRICE.REALPRICECODE
    //    AND REALPRICE.SCODE=S.SCODE)
    //'))->first());

})->describe('Display an inspiring quote');
