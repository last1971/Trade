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
    Route::get('invoice/upd2-xml/{id}', 'Api\InvoiceController@upd2Xml')->name('invoice.upd2-xml');
    Route::post('mark-codes/mark-as-transferred', 'Api\MarkCodeController@markAsTransferred')->name('mark-codes.mark-as-transferred');
    Route::post('mark-codes/unmark-as-transferred', 'Api\MarkCodeController@unmarkAsTransferred')->name('mark-codes.unmark-as-transferred');
    Route::get('invoice/receipt/{invoice}', 'Api\InvoiceController@receipt')->name('invoice.receipt');
    Route::get('invoice/etiks', 'Api\InvoiceController@etiks')->name('invoice.etiks');
    Route::get('invoice-line/export/', 'Api\InvoiceLineController@export')->name('invoice-line.xlsx');
    Route::middleware('permission:buyer-debt.index')->group(function () {
        Route::get('buyer-debt/export', 'Api\BuyerDebtController@export')->name('buyer-debt.xlsx');
        Route::get('buyer-debt/report', 'Api\BuyerDebtController@report')->name('buyer-debt.report');
        Route::get('buyer-debt/summary', 'Api\BuyerDebtController@summary')->name('buyer-debt.summary');
    });

    Route::middleware('permission:replenish.index')->group(function () {
        Route::get('replenish/list', 'Api\ReplenishController@list')->name('replenish.list');
        Route::get('replenish/report', 'Api\ReplenishController@report')->name('replenish.report');
        Route::get('replenish/list-export', 'Api\ReplenishController@listExport')->name('replenish.list-xlsx');
        Route::get('replenish/report-export', 'Api\ReplenishController@reportExport')->name('replenish.report-xlsx');
    });
    Route::get('transfer-out-line/export/', 'Api\TransferOutLineController@export')
        ->name('transfer-out-line.xlsx');
    Route::get('transfer-out/pdf-token/{id}', 'Api\TransferOutController@pdfToken')
        ->name('transfer-out.pdf.token');
    Route::get('transfer-out/export/{id}', 'Api\TransferOutController@pdf')
        ->name('transfer-out.pdf');
    Route::get('transfer-out/xml/{id}', 'Api\TransferOutController@xml')
        ->name('transfer-out.xml');
    Route::get('config', 'Api\ConfigController@index')->name('config.index');
    Route::get('exchange-rate', 'Api\ExchangeRateController@index')->name('exchange-rate.index');
    Route::get('seller-price', 'Api\SellerPriceController@index')->name('seller-price.index');
    Route::get('seller-price/own', 'Api\SellerPriceController@show')->name('seller-price.show');
    Route::get('seller-price/blocked', 'Api\SellerPriceController@blocked')
        ->name('seller-price.blocked');
    Route::get('seller-price/sellers', 'Api\SellerPriceController@sellers')
        ->name('seller-price.sellers');

    Route::middleware('permission:certificate.index')->group(function () {
        Route::get('certificate/{id}/download', 'Api\CertificateController@download')
            ->name('certificate.download');
        Route::get('certificate-marketplaces', 'Api\CertificateController@marketplaces')
            ->name('certificate.marketplaces');
        Route::get('certificate-types', 'Api\CertificateController@types')
            ->name('certificate.types');
        Route::get('good/{id}/certificates', 'Api\CertificateController@forGood')
            ->name('good.certificates');
    });
    Route::middleware('permission:certificate.update')->group(function () {
        Route::post('certificate/{id}/goods', 'Api\CertificateController@attachGoods')
            ->name('certificate.attach-goods');
        Route::delete('certificate/{id}/goods/{goodId}', 'Api\CertificateController@detachGood')
            ->name('certificate.detach-good');
        Route::post('certificate/{id}/marketplaces', 'Api\CertificateController@markMarketplace')
            ->name('certificate.mark-marketplace');
        Route::delete('certificate/{id}/marketplaces/{marketplaceId}', 'Api\CertificateController@unmarkMarketplace')
            ->name('certificate.unmark-marketplace');
    });

    Route::apiResources([
        'advanced-buyer' => 'Api\AdvancedBuyerController',
        'buyer' => 'Api\BuyerController',
        'cash-flow' => 'Api\CashFlowController',
        'category' => 'Api\CategoryController',
        'certificate' => 'Api\CertificateController',
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
    Route::post('sbis/upd-import', 'Api\SbisController@updImport')->name('sbis.upd-import');
    Route::post('sbis/wildberries-import', 'Api\SbisController@wildberriesImport')->name('sbis.wildberries-import');

    Route::post('goods-list', 'Api\GoodsListController@store')->name('goods-list.store');

    Route::put('seller-good/{sellerGood}', 'Api\SellerGoodController@update')->name('seller.good.update');
    
    Route::post('seller-order/{id}/lines', 'Api\SellerOrderController@addLines')->name('seller-order.add-lines');
    Route::get('seller-order/{id}/lines', 'Api\SellerOrderController@getLines')->name('seller-order.get-lines');
    Route::put('seller-order/{id}/lines', 'Api\SellerOrderController@updateLineQuantity')->name('seller-order.update-line');
    Route::delete('seller-order/{id}/lines', 'Api\SellerOrderController@deleteLine')->name('seller-order.delete-line');
    Route::post('seller-order/{id}/send-invoice', 'Api\SellerOrderController@sendInvoice')->name('seller-order.send-invoice');
    Route::post('seller-order/{id}/ship', 'Api\SellerOrderController@shipOrder')->name('seller-order.ship');
    
    Route::get('compel/delivery-modes', 'Api\CompelController@getDeliveryModes')->name('compel.delivery-modes');
});

Route::post('login', 'Api\AuthController@login')->name('login');
Route::post('register', 'Api\AuthController@register')->name('register');
Route::post('forgot', 'Api\AuthController@forgot')->name('forgot');
Route::post('check-token', 'Api\AuthController@checkToken')->name('checkToken');
Route::post('reset-password', 'Api\AuthController@reset')->name('resetPassword');
