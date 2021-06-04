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

Route::get('/{path?}', function () {
    return view('app');
});
Route::get('/{path?}/{id?}', function () {
    return view('app');
});
