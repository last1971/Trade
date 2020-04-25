<?php

use App\Services\InvoiceService;
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
    //dd( Storage::path('storage/fonts/stamp.png'));
    $s = new InvoiceService();
    $a = collect([
        'with' => ['invoiceLines.name', 'firm', 'buyer', 'invoiceLines.good'],
    ]);
    $invoice = $s->index($a)->orderBy('SCODE', 'desc')->first();
    //dd($invoice->firm);
    $pdf = PDF::loadView('invoice-pdf', compact('invoice'));
    $pdf->save('test.pdf');
    //->join('S as invoice', 'invoice.SCODE', '=', 'REALPRICE.SCODE')
    //->whereIn('invoice.STATUS', [1])

})->describe('Display an inspiring quote');
