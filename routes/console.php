<?php

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

Artisan::command('test', function () {
    dd(asset('/t/t'));
    /*
    $row = [
        "Наименование" => "2-1393222-0",
        "Кол" => 4,
        "Цена" => 152.52,
        0 => null
    ];
    $newRow = [
        'name' => ['наименование', 'название', 'имя', 'name'],
        'quantity' => ['количество', 'кол.', 'кол', 'кол-во', 'кол.-во', 'qnt', 'quan', 'quantity'],
        'price' => ['цена', 'цена товара', 'price'],
        'priceWithoutVat' => ['цена без ндс', 'price without vat'],
        'amount' => ['сумма', 'валютная сумма', 'amount'],
        'amountWithoutVat' => ['сумма с ндс'],
        'multiplicity' => ['кратность', 'Цена указана за ... шт.'],
        'country' => ['страна происхождения', 'страна', 'country'],
        'declaration' => ['код таможенной декларации', 'гтд', 'declaration'],
        'producer' => ['производитель', 'producer'],
        'case' => ['case', 'корпус'],
    ];
    array_walk($newRow, function (&$value) use ($row) {
        foreach ($row as $rowKey => $rowValue) {
            if (in_array(Str::lower($rowKey), $value, true)) {
                $value = $rowValue;
                break;
            }
        }
    });
    dd($newRow);*/
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
