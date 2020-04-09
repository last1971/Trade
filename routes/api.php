<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

//Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
//});

Route::middleware('auth:api')->group(function () {
    Route::get('logout', 'Api\AuthController@logout')->name('logout');
    Route::get('refresh-user', 'Api\AuthController@refresh')->name('refresh');
    Route::apiResources([
        'buyer' => 'Api\BuyerController',
        'invoice' => 'Api\InvoiceController',
        'invoice-line' => 'Api\InvoiceLineController',
        'transfer-out' => 'Api\TransferOutController',
        'transfer-out-line' => 'Api\TransferOutLineController',
        'user' => 'Api\UserController',
        'user-option' => 'Api\UserOptionController'
    ]);
});

Route::post('login', 'Api\AuthController@login')->name('login');
Route::post('register', 'Api\AuthController@register')->name('register');
