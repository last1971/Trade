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
    Route::get('invoice/export/', 'Api\InvoiceController@export')->name('invoice.xlsx');
    Route::get('invoice/export/{id}', 'Api\InvoiceController@pdf')->name('invoice.pdf');
    Route::get('invoice-line/export/', 'Api\InvoiceLineController@export')->name('invoice-line.xlsx');
    Route::get('transfer-out-line/export/', 'Api\TransferOutLineController@export')
        ->name('transfer-out-line.xlsx');
    Route::get('transfer-out/export/{id}', 'Api\TransferOutController@pdf')
        ->name('transfer-out.pdf');
    Route::get('transfer-out/xml/{id}', 'Api\TransferOutController@xml')
        ->name('transfer-out.xml');

    Route::apiResources([
        'advanced-buyer' => 'Api\AdvancedBuyerController',
        'buyer' => 'Api\BuyerController',
        'category' => 'Api\CategoryController',
        'employee' => 'Api\EmployeeController',
        'firm' => 'Api\FirmController',
        'good' => 'Api\GoodController',
        'invoice' => 'Api\InvoiceController',
        'invoice-line' => 'Api\InvoiceLineController',
        'name' => 'Api\NameController',
        'order' => 'Api\OrderController',
        'order-import-line' => 'Api\OrderImportLineController',
        'order-line' => 'Api\OrderLineController',
        'order-step' => 'Api\OrderStepController',
        'reserve' => 'Api\ReserveController',
        'retail-price' => 'Api\RetailPriceController',
        'retail-order-line' => 'Api\RetailOrderLineController',
        'role' => 'Api\RoleController',
        'seller' => 'Api\SellerController',
        'transfer-out' => 'Api\TransferOutController',
        'transfer-out-line' => 'Api\TransferOutLineController',
        'user' => 'Api\UserController',
        'user-option' => 'Api\UserOptionController'
    ]);

    Route::post('sbis/xlsx', 'Api\SbisController@xlsx')->name('sbis.xlsx');
    Route::post('sbis/clear-gtd', 'Api\SbisController@clearGtd')->name('sbis.clear-gtd');
    Route::post('sbis/export', 'Api\SbisController@export')->name('sbis.export');
});

Route::post('login', 'Api\AuthController@login')->name('login');
Route::post('register', 'Api\AuthController@register')->name('register');
Route::post('forgot', 'Api\AuthController@forgot')->name('forgot');
Route::post('check-token', 'Api\AuthController@checkToken')->name('checkToken');
Route::post('reset-password', 'Api\AuthController@reset')->name('resetPassword');
