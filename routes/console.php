<?php

use App\PaymentOrder;
use App\SellerPrice;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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

Artisan::command('test1', function () {
    $q = \App\StoreLine::query()->with('fifos')
        ->where('GOODSCODE', 507933)
        ->withSum('fifos', 'QUAN')
        ->where('QUAN', '>', function($query) {
            $query = $query->from('FIFO_T')
                ->join('PR_META', 'PR_META.ID', '=', 'FIFO_T.PR_META_IN_ID')
                ->whereColumn('PR_META.SKLADINCODE', '=', 'SKLADIN.SKLADINCODE')
                ->selectRaw('COALESCE(SUM(FIFO_T.QUAN), 0)');
        })
        ->get();
    dd($q);
   $s = \App\StoreLine::query()->whereHas('fifos', function (Illuminate\Database\Eloquent\Builder $query) {
       $query->select('FIFO_T.ID')
           ->selectRaw('sum(FIFO_T.QUAN) AS sails')
                   ->havingRaw('sum(FIFO_T.QUAN) < SKLADIN.QUAN');

   })->first();
   dd($s);
})->describe('Test');

Artisan::command('clear-retail', function () {
    $connection = DB::connection('firebird');
    $connection->getPdo()->setAttribute(PDO::ATTR_AUTOCOMMIT, 0);
    $connection->beginTransaction();
    try {
        $connection->statement('UPDATE RESERVEDPOS SET QUANSKLAD=0');
        $query = \App\Warehouse::query()
            ->where('QUAN', '>', '0')
            ->whereNotIn('GOODSCODE', [356804]);
        $counter = $query->count();
        echo $counter . PHP_EOL;
        foreach ($query->get() as $line) {
            echo $counter-- . ' - ' . $line->GOODSCODE . PHP_EOL;
            $connection->statement(
                'EXECUTE PROCEDURE spisanie_univ1(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $line->GOODSCODE,
                    'Automatic initial',
                    $line->QUAN,
                    'Automatic initial',
                    0,
                    0.01,
                    \Carbon\Carbon::now(),
                    25,
                    null,
                    null,
                    10
                ]
            );
        }
        $connection->commit();
    } catch (\Exception $e) {
        echo $e->getMessage() . PHP_EOL;
        $connection->rollBack();
    }
})->describe('Clear retail');

Artisan::command('clear-wholesale', function () {
    $connection = DB::connection('firebird');
    $connection->getPdo()->setAttribute(PDO::ATTR_AUTOCOMMIT, 0);
    $connection->beginTransaction();
    try {
        $connection->statement('UPDATE RESERVEDPOS SET QUANSHOP=0');
        $query = \App\RetailStore::query()
            ->where('QUAN', '>', '0');
        $counter = $query->count();
        echo $counter . PHP_EOL;
        foreach ($query->get() as $line) {
            echo $counter-- . ' - ' . $line->GOODSCODE . PHP_EOL;
            $connection->statement(
                'EXECUTE PROCEDURE spisanie_univ1(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $line->GOODSCODE,
                    'Automatic initial',
                    $line->QUAN,
                    'Automatic initial',
                    1,
                    0.01,
                    \Carbon\Carbon::now(),
                    25,
                    null,
                    null,
                    10
                ]
            );
        }
        $connection->commit();
    } catch (\Exception $e) {
        echo $e->getMessage() . PHP_EOL;
        $connection->rollBack();
    }
})->describe('Clear Wholesale');
