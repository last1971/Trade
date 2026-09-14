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
    Route::post('tnved/match', 'Api\TnvedController@match')->name('tnved.match');
    Route::get('marking/dict', 'Api\TnvedController@markingDict')->name('marking.dict');
    Route::get('marking/goods', 'Api\TnvedController@markGoods')->name('marking.goods');
    Route::get('tnved/{code}', 'Api\TnvedController@show')->where('code', '[0-9]+')->name('tnved.show');
    Route::get('logout', 'Api\AuthController@logout')->name('logout');
    Route::get('refresh-user', 'Api\AuthController@refresh')->name('refresh');
    Route::get('invoice/export/', 'Api\InvoiceController@export')->name('invoice.xlsx');
    Route::get('invoice/export/{id}', 'Api\InvoiceController@pdf')->name('invoice.pdf');
    Route::get('invoice/upd2-xml/{id}', 'Api\InvoiceController@upd2Xml')->name('invoice.upd2-xml');
    Route::post('invoice/mp-upd-xml/{id}', 'Api\InvoiceController@mpUpdXml')->name('invoice.mp-upd-xml');
    Route::get('mark-codes/transfer-state', 'Api\MarkCodeController@transferState')->name('mark-codes.transfer-state');
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

    Route::middleware('permission:stock-classif.index')->group(function () {
        Route::get('stock-classif', 'Api\StockClassifController@index')
            ->name('stock-classif.index');
        Route::get('stock-classif/status', 'Api\StockClassifController@status')
            ->name('stock-classif.status');
        Route::get('stock-classif/categories', 'Api\StockClassifController@categories')
            ->name('stock-classif.categories');
        Route::post('stock-classif/refresh', 'Api\StockClassifController@refresh')
            ->name('stock-classif.refresh');
    });

    Route::middleware('permission:good.show')->group(function () {
        Route::get('good/{id}/gtins', 'Api\GoodGtinController@forGood')
            ->name('good.gtins');
        Route::get('good/{id}/uncovered', 'Api\GoodGtinController@uncovered')
            ->name('good.uncovered');
        // Выпуск КМ (chz-сервис): чтение
        Route::get('chz/gtin/{gtin}/orders', 'Api\ChzOrderController@ordersByGtin')
            ->name('chz.gtin.orders');
        Route::get('chz/order/{orderId}/status', 'Api\ChzOrderController@status')
            ->name('chz.order.status');
        Route::get('chz/order/{orderId}/pdf', 'Api\ChzOrderController@pdfList')
            ->name('chz.order.pdf');
        Route::get('chz/order/{orderId}/pdf/{n}', 'Api\ChzOrderController@pdfChunk')
            ->name('chz.order.pdf-chunk');
        Route::get('chz/order/{orderId}/codes.csv', 'Api\ChzOrderController@codesCsv')
            ->name('chz.order.codes-csv');
    });
    // Очередь отправки в ЧЗ: что уехало, что ждёт, что отбито. Право то же,
    // что у списка марок — это его же хозяйство.
    Route::middleware('permission:mark-code.index')->group(function () {
        // Список пачек — общим механизмом таблиц (фильтры, сортировка, страницы).
        // Имя маршрута определяет право '<имя до точки>.full' в ModelController.
        Route::get('chz-outbox', 'Api\ChzOutboxController@index')
            ->name('chz-outbox.index');
        Route::get('chz/outbox/state', 'Api\ChzOutboxController@state')
            ->name('chz.outbox.state');
        Route::get('chz/outbox/{id}/codes', 'Api\ChzOutboxController@codes')
            ->name('chz.outbox.codes');
        // Карточка кода: что о нём думает ГИС МТ прямо сейчас
        Route::get('mark-code/{id}/chz-info', 'Api\MarkCodeController@chzInfo')
            ->name('mark-code.chz-info');
        // Карточка кода живёт по MARKCODE, а со сканера в руках известен КИ —
        // этот маршрут их и связывает.
        Route::get('mark-code-find', 'Api\MarkCodeController@find')
            ->name('mark-code.find');
    });

    Route::middleware('permission:good.update')->group(function () {
        Route::post('good/{id}/classify', 'Api\GoodGtinController@classify')
            ->name('good.classify');
        // Выпуск КМ: заказ в СУЗ платный, поэтому под good.update
        Route::post('chz/gtin/{gtin}/orders', 'Api\ChzOrderController@order')
            ->name('chz.gtin.order');
        // Повтор отбитой пачки: новая отправка в ЧЗ, поэтому право то же, что у заказа
        Route::post('chz/outbox/{id}/retry', 'Api\ChzOutboxController@retry')
            ->name('chz.outbox.retry');
        // Снять коды с отправки и вернуть обратно: решение человека, что с кодом
        // делать в ЧЗ, — то же по весу, что и отправка.
        Route::post('chz/outbox/{id}/skip', 'Api\ChzOutboxController@skip')
            ->name('chz.outbox.skip');
        Route::post('chz/outbox/{id}/unskip', 'Api\ChzOutboxController@unskip')
            ->name('chz.outbox.unskip');
        Route::post('mark-code/{id}/unskip', 'Api\MarkCodeController@unskip')
            ->name('mark-code.unskip');
        Route::post('good/{id}/suggest', 'Api\GoodGtinController@suggest')
            ->name('good.suggest');
        Route::post('good/classify-bulk', 'Api\GoodGtinController@classifyBulk')
            ->name('good.classify-bulk');
        Route::get('tnved-suggestions', 'Api\TnvedSuggestController@index')
            ->name('tnved-suggestions.index');
        Route::get('tnved-suggestions/status', 'Api\TnvedSuggestController@status')
            ->name('tnved-suggestions.status');
        Route::post('tnved-suggestions/run', 'Api\TnvedSuggestController@run')
            ->name('tnved-suggestions.run');
        Route::post('tnved-suggestions/apply', 'Api\TnvedSuggestController@apply')
            ->name('tnved-suggestions.apply');
        Route::post('good/{id}/gtins', 'Api\GoodGtinController@store')
            ->name('good.gtins.store');
        Route::put('good-gtin/{id}', 'Api\GoodGtinController@update')
            ->name('good.gtins.update');
        Route::delete('good-gtin/{id}', 'Api\GoodGtinController@destroy')
            ->name('good.gtins.destroy');
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

    // Марки ЧЗ, приходы и списания — только просмотр
    Route::apiResource('mark-code', 'Api\MarkCodeController')->only(['index', 'show']);
    Route::apiResource('store-in', 'Api\StoreInController')->only(['index', 'show']);
    Route::apiResource('spis-sklad', 'Api\SpisSkladController')->only(['index', 'show']);

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
