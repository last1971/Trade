<?php

use App\TransferOut;
use App\TransferOutLine;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
Route::get('/test1/', function () {
    $transferOut = TransferOut::with('firm', 'buyer', 'employee')->find(68451);
    $transferOutLines = TransferOutLine::with(['category', 'good', 'name'])
        ->where('SFCODE', '=', 68451)
        ->get();
    $cashFlows = $transferOut->invoice->cashFlows->filter(function ($v) {
        return !$v->SFCODE1;
    });
    return view('transfer-out-pdf', compact('transferOut', 'transferOutLines', 'cashFlows'));
})->name('test1.t');
Route::get('/test2/{test2}', 'TestController@test');

Route::get('/{path?}', function () {
    return view('app');
});
Route::get('/{path?}/{id?}', function () {
    return view('app');
});
