<?php

use App\Invoice;
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
    /*
    $pdo = new \PDO('firebird:dbname=192.168.14.200/3050:/home/db/base.fdb;charset=UTF8', 'SYSDBA', '641767');
    // dd($pdo);
    $sql = "SELECT first 10 * FROM realprice";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    dd($stmt->fetch());
    */
    //$res = \App\User::where('sdsd','sdsd')->first();
    $res = Invoice::query()->with('employee.user')->orderBy('DATA', 'desc')->first();
    dd($res);
})->describe('Display an inspiring quote');
