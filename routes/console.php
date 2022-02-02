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

Artisan::command('test1', function (\App\Services\CompelApiService $s) {
    dd($s->searchInCenter('RES 0805 100K 5%'));
    /*
    $pricesQuery = SellerPrice::query()
        ->whereDate('updated_at', '<', \Carbon\Carbon::now()->subDays(7))->select('seller_warehouse_id');
    $warehousesQuery = \App\SellerWarehouse::query()
        ->whereIn('id', $pricesQuery)->select('seller_good_id');
    $goodsQuery = \App\SellerGood::query()
        ->whereIn('id', $warehousesQuery)
        ->where('is_active', true)
        ->select('id');
    $i = 0;
    while ($goodsQuery->count() > 0) {
        $goods = $goodsQuery
            ->take(1000)
            ->get()
            ->map(fn($v) => $v->id)
            ->toArray();
        $i += \App\SellerGood::query()->whereIn('id', $goods)->update(['is_active' => false ]);
        echo $i . PHP_EOL;
    }
    */
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
