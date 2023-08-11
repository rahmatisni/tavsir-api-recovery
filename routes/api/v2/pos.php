<?php

use App\Http\Controllers\API\PPOBDashboardController;
use App\Http\Controllers\API\V2\Pos\ProductBahanBakuController;
use App\Http\Controllers\API\V2\Pos\ProductTunggalController;
use App\Http\Controllers\API\V2\Pos\ProductV2Controller;
use App\Http\Controllers\API\V2\POS\RawProductController;
use App\Http\Controllers\API\V2\Pos\TransStockV2Controller;
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
Route::middleware('auth:api')->group(function () {
    Route::prefix('product-bahan-baku')->controller(ProductBahanBakuController::class)->group(function () {
        Route::get('','index');
        Route::get('/{id}','show');
        Route::post('','store');
        Route::post('/{id}','update');
        Route::post('/change-status/{id}','changeStatus');
        Route::delete('/{id}','destroy');
    });

    Route::prefix('product-tunggal')->controller(ProductTunggalController::class)->group(function () {
        Route::get('','index');
        Route::get('/{id}','show');
        Route::post('','store');
        Route::post('/{id}','update');
        Route::post('/change-status/{id}','changeStatus');
        Route::delete('/{id}','destroy');
    });

    Route::prefix('stock')->controller(TransStockV2Controller::class)->group(function () {
        Route::get('kartu','kartu');
        Route::get('kartu/{id}','showKartu');
        Route::get('show/{id}','showMasukKeluar');
        Route::get('masuk','masuk');
        Route::post('masuk','storeMasuk');
        Route::get('keluar','keluar');
        Route::post('keluar','storeKeluar');
        Route::post('change-status','changeStatus');
    });
});