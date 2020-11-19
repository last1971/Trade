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
    $s = new \App\Services\UCSService();
    $s->send($s::LOGIN, 1);
    $r = $s->get();
    dd($r);
    //$s->close();
    //dd(new \App\Services\UCSService());
    $fp = stream_socket_client("tcp://192.168.12.201:4001", $errno, $errstr, 30);
    if (!$fp) {
        echo "$errstr ($errno)<br />\n";
    } else {
        fwrite($fp, "1000773460480C000000886000");
        while ( !feof($fp)) {
            echo iconv("cp1251", "utf-8", fgets($fp, 2));
        }
        fclose($fp);
    }
    exit;
    //dd(floatval('40.00'));
    //dd(\App\RetailSale::from(\Illuminate\Support\Facades\DB::raw("select_outday1(?, null)"))->setBindings(['2020-11-12'])->where('SUMMA', '<', 100)->take(10)->get());
    $s = new \App\Services\ModelService(\App\RetailSale::class);
    $s->setRawFrom('select_outday1(?, null) as retail_sales', ['12.11.2020']);
    dd($s->index(collect([
        'selectAttributes' => '*',
        'filterAttributes' => ['SUMMA'],
        'filterOperators' => ['<'],
        'filterValues' => [30]

    ]))->get());
    $service->operator = \App\User::firstWhere('email', 'elcopro@gmail.com');
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
