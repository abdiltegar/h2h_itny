<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RestController;
use App\Http\Controllers\BniController;

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

Route::get('/', function () {
    return view('welcome');
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