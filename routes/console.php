<?php

use App\Services\ForDeleteService;
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

Artisan::command('test', function (\App\Services\AtolService $service) {
    //dd(\App\RetailSale::from(\Illuminate\Support\Facades\DB::raw("select_outday1(?, null)"))->setBindings(['2020-11-12'])->where('SUMMA', '<', 100)->take(10)->get());
    $s = new \App\Services\ModelService(\App\RetailSale::class);
    $s->setRawFrom('select_outday1(?, null) as retail_sales', ['12.11.2020']);
    dd($s->index(collect([
        'selectAttributes' => '*',
        'filterAttributes' => ['SUMMA'],
        'filterOperators' => ['<'],
        'filterValues' => [30]

    ]))->get());
    $service->operator = \App\User::firstWhere('email', 'retailer@test.test');
    if (!$service->connect()) {
        echo "activate ";
        $service->activateDevice();
    }
    /*
    echo 'sale ';
    $service->receipt([
        [
            'name' => '1N4007',
            'price' => 0.55,
            'quantity' => 5,
            'amount' => 2.75,
        ],
        [
            'name' => 'BC847C',
            'price' => 2.01,
            'quantity' => 1,
            'amount' => 2.01,
        ]
    ], 'sellReturn');
    $service->deactivateDevice();
    */
    $service->closeShift();
    $service->deactivateDevice();
})->describe('Display an inspiring quote');
