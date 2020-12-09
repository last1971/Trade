<?php

use App\Services\ForDeleteService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

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

Artisan::command('clear-retail', function () {
    $connection = DB::connection('firebird');
    $connection->getPdo()->setAttribute(PDO::ATTR_AUTOCOMMIT, 0);
    $connection->beginTransaction();
    try {
        $connection->statement('UPDATE RESERVEDPOS SET QUANSKLAD=0');
        $counter = 0;
        $query = \App\Warehouse::query()
            ->where('QUAN', '>', '0')
            ->whereNotIn('GOODSCODE', [356804]);
        echo $query->count() . PHP_EOL;
        $query->chunk(100, function ($lines) use ($connection, &$counter) {
                echo 'Counter: ' . $counter . PHP_EOL;
                foreach ($lines as $line) {
                    echo $line->GOODSCODE . PHP_EOL;
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
                $counter++;
            });
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
        echo $query->count() . PHP_EOL;
        foreach ($query->get() as $line) {
            echo $line->GOODSCODE . PHP_EOL;
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
})->describe('Clear retail');
