<?php

use App\Services\DigiKeyApiService;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

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

Route::get('/test2/{test2}', 'TestController@test');

Route::get('/chipdip/search/{search}', function ($search, \Last1971\ChipDipParser\ChipDipParser $parser) {
    dd($parser->searchByName($search));
});

Route::get('/chipdip/code/{code}', function ($code, \Last1971\ChipDipParser\ChipDipParser $parser) {
    dd($parser->searchByCode($code));
});

Route::get('/digi-key', function (Request $request, DigiKeyApiService $service) {
    if ($request->get('code')) {
        $service->gettingTheAccessToken($request->get('code'));
        return redirect('/home');
    }
    return redirect($service->gettingTheAuthorizationCodeUri());
});

Route::get('/pdf/transfer-out/{id}', function ($id, Request $request) {
    $token = $request->get('token');
    if (!$token) {
        abort(403, 'Token required');
    }

    $data = \Illuminate\Support\Facades\Cache::pull('pdf_token_' . $token);
    if (!$data || $data['transfer_out_id'] != $id) {
        abort(403, 'Invalid token');
    }

    // Авторизуем пользователя
    $user = \App\User::findOrFail($data['user_id']);
    \Illuminate\Support\Facades\Auth::setUser($user);

    // Вызываем метод контроллера через app()->call() для автоматической инъекции зависимостей
    return app()->call('App\Http\Controllers\Api\TransferOutController@pdf', ['id' => $id]);
})->name('transfer-out.pdf.public');

Route::get('/{path?}', function () {
    return view('app');
});
Route::get('/{path?}/{id?}', function () {
    return view('app');
});
