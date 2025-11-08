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
    Route::get('invoice/receipt/{invoice}', 'Api\InvoiceController@receipt')->name('invoice.receipt');
    Route::get('invoice/etiks', 'Api\InvoiceController@etiks')->name('invoice.etiks');
    Route::get('invoice-line/export/', 'Api\InvoiceLineController@export')->name('invoice-line.xlsx');
    Route::get('transfer-out-line/export/', 'Api\TransferOutLineController@export')
        ->name('transfer-out-line.xlsx');
    Route::get('transfer-out/export/{id}', 'Api\TransferOutController@pdf')
        ->name('transfer-out.pdf');
    Route::get('transfer-out/xml/{id}', 'Api\TransferOutController@xml')
        ->name('transfer-out.xml');
    Route::get('exchange-rate', 'Api\ExchangeRateController@index')->name('exchange-rate.index');
    Route::get('seller-price', 'Api\SellerPriceController@index')->name('seller-price.index');
    Route::get('seller-price/own', 'Api\SellerPriceController@show')->name('seller-price.show');
    Route::get('seller-price/sellers', 'Api\SellerPriceController@sellers')
        ->name('seller-price.sellers');

    Route::apiResources([
        'advanced-buyer' => 'Api\AdvancedBuyerController',
        'buyer' => 'Api\BuyerController',
        'cash-flow' => 'Api\CashFlowController',
        'category' => 'Api\CategoryController',
        'employee' => 'Api\EmployeeController',
        'firm' => 'Api\FirmController',
        'firm-history' => 'Api\FirmHistoryController',
        'good' => 'Api\GoodController',
        'invoice' => 'Api\InvoiceController',
        'invoice-line' => 'Api\InvoiceLineController',
        'name' => 'Api\NameController',
        'order' => 'Api\OrderController',
        'order-import-line' => 'Api\OrderImportLineController',
        'order-line' => 'Api\OrderLineController',
        'order-step' => 'Api\OrderStepController',
        'payment' => 'Api\PaymentController',
        'payment-order' => 'Api\PaymentOrderController',
        'reserve' => 'Api\ReserveController',
        'retail-price' => 'Api\RetailPriceController',
        'retail-order-line' => 'Api\RetailOrderLineController',
        'role' => 'Api\RoleController',
        'seller' => 'Api\SellerController',
        'seller-order' => 'Api\SellerOrderController',
        'store-line' => 'Api\StoreLineController',
        'transfer-out' => 'Api\TransferOutController',
        'transfer-out-line' => 'Api\TransferOutLineController',
        'user' => 'Api\UserController',
        'user-option' => 'Api\UserOptionController',
        'unit-code' => 'Api\UnitCodeController',
        'unit-code-alias' => 'Api\UnitCodeAliasController',
    ]);

    Route::get('retail-sale', 'Api\RetailSaleController@index')->name('retail-sale.index');

    Route::get('retail-store-return', 'Api\RetailStoreReturnController@index')
        ->name('retail-store-return.index');

    Route::get('retail-sale-line', 'Api\RetailSaleLineController@index')
        ->name('retail-sale-line.index');
    Route::delete('retail-sale-line', 'Api\RetailSaleLineController@refund')
        ->name('retail-sale-line.destroy');

    Route::post('sbis/xlsx', 'Api\SbisController@xlsx')->name('sbis.xlsx');
    Route::post('sbis/clear-gtd', 'Api\SbisController@clearGtd')->name('sbis.clear-gtd');
    Route::post('sbis/export', 'Api\SbisController@export')->name('sbis.export');
    Route::post('sbis/packing-list', 'Api\SbisController@packingList')->name('sbis.packing-list');

    Route::post('goods-list', 'Api\GoodsListController@store')->name('goods-list.store');

    Route::put('seller-good/{sellerGood}', 'Api\SellerGoodController@update')->name('seller.good.update');
    
    Route::post('seller-order/{id}/lines', 'Api\SellerOrderController@addLines')->name('seller-order.add-lines');
    Route::get('seller-order/{id}/lines', 'Api\SellerOrderController@getLines')->name('seller-order.get-lines');
    Route::put('seller-order/{id}/lines', 'Api\SellerOrderController@updateLineQuantity')->name('seller-order.update-line');
    Route::delete('seller-order/{id}/lines', 'Api\SellerOrderController@deleteLine')->name('seller-order.delete-line');
});

Route::post('login', 'Api\AuthController@login')->name('login');
Route::post('register', 'Api\AuthController@register')->name('register');
Route::post('forgot', 'Api\AuthController@forgot')->name('forgot');
Route::post('check-token', 'Api\AuthController@checkToken')->name('checkToken');
Route::post('reset-password', 'Api\AuthController@reset')->name('resetPassword');
