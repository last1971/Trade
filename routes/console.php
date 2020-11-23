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
    /*$s = new \App\Services\UCSService();
    $s->send($s::LOGIN, '1');
    $r = $s->receive();
    $s->close();
    dd($r);
    exit;
*/

    $address = env('UCS_IP');
    $service_port = 4001;
    $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    if ($socket === false) {
        echo "Не удалось выполнить socket_create(): причина: " . socket_strerror(socket_last_error()) . "\n";
    } else {
        echo "OK.\n";
    }

    echo "Пытаемся соединиться с '$address' на порту '$service_port'...";
    $result = socket_connect($socket, '192.168.12.201', 4001);
    if ($result === false) {
        echo "Не удалось выполнить socket_connect().\nПричина: ($result) " . socket_strerror(socket_last_error($socket)) . "\n";
    } else {
        echo "OK.\n";
    }

    socket_write($socket, '1000773460480C000000886000', 26);
    echo "Читаем ответ:\n\n";
    $out = socket_read($socket, 1000);
        echo $out;
    $out = '';
    while ($out === '') {
        sleep(1);
        $out = socket_read($socket, 1000);
    }
    echo '-'.$out;
    echo "Закрываем сокет...";
    socket_close($socket);
    echo "OK.\n\n";
    exit;


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
