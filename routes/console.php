<?php

use App\Services\TransferOutService;
use App\TransferOut;
use App\TransferOutLine;
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
    //$res = $s->xml(collect(['transferOut' => 68451, 'director' => true]));
    //$t= new \App\Services\SbisService();
    //\Illuminate\Support\Facades\Storage::put('test.html', $t->xml2html($res));
    $transferOut = TransferOut::with('firm', 'buyer', 'employee', 'invoice.cashFlows')->find(59047);
    $cashFlows = $transferOut->invoice->cashFlows->filter(function ($v) {
        return !$v->SFCODE1;
    });
    $transferOutLines = TransferOutLine::with(['category', 'good', 'name'])
        ->where('SFCODE', '=', 59047)
        ->get();
    $pdf = PDF::loadView('transfer-out-pdf', compact('transferOutLines', 'transferOut', 'cashFlows'));
    $pdf->setPaper('A4', 'landscape');
    $pdf->save('invoice.pdf');
})->describe('Display an inspiring quote');
