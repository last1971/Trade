<?php

use App\Services\InvoiceLineService;
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
    /*$dsn = 'firebird:dbname=192.168.14.200:/home/db/base.fdb;charset=utf8;';
    $username = 'SYSDBA';
    $password = '641767';
    // Подключение к БД
    $dbh = new \PDO($dsn, $username, $password, [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        \PDO::FB_ATTR_DATE_FORMAT => 'd.m.Y'
        ]);
    $stmt = $dbh->prepare('SELECT * FROM S where DATA > ?');
    $stmt->execute(['"01.01.2020"']);
    dd($stmt->fetchObject());*/
    $s = new InvoiceLineService();
    $a = collect([
        'filterAttributes' => ['invoice.STATUS'],
        'filterOperators' => ['IN'],
        'filterValues' => ['1,2']
    ]);
    dd($s->index($a)
        //->join('S as invoice', 'invoice.SCODE', '=', 'REALPRICE.SCODE')
        //->whereIn('invoice.STATUS', [1])
        ->whereRaw('"invoice"."NS" = 1')
        ->first()
    );
})->describe('Display an inspiring quote');
