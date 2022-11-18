<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BniController;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::controller(RestController::class)->group(function () {
    Route::get('test', 'index')->name('test');

    Route::get('/inquiry', 'Inquiry')->name('inquiry');
    Route::get('/payment', 'Payment')->name('payment');
});

Route::controller(BniController::class)->group(function () {
    Route::get('/bni/create_va', 'Inquiry')->name('bni.createva.get');
    Route::post('/bni/create_va', 'Inquiry')->name('bni.createva.post');

    Route::get('/bni/payment', 'CallBack')->name('bni.payment.get');
    Route::post('/bni/payment', 'CallBack')->name('bni.payment.post');
});