<?php

use App\Services\TransferOutService;
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

Artisan::command('test', function (TransferOutService $s) {
    $res = $s->xml(68454, collect(['director' => false]));
    $o = simplexml_load_string($res);
    \Illuminate\Support\Facades\Storage::put((string)$o->attributes()["ИдФайл"], $res);
})->describe('Display an inspiring quote');
