<?php

use Illuminate\Http\Request;
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
    Route::get('/profile', [App\Http\Controllers\API\AuthController::class, 'profile']);
    
    Route::post('/logout', [App\Http\Controllers\API\AuthController::class,'logout']);
    Route::apiResource('rest-area', App\Http\Controllers\API\RestAreaController::class);
    Route::apiResource('business', App\Http\Controllers\API\BusinessController::class);
    Route::apiResource('tenant', App\Http\Controllers\API\TenantController::class);
    Route::apiResource('product', App\Http\Controllers\API\ProductController::class);
    Route::apiResource('payment-method', App\Http\Controllers\API\PaymentMethodController::class);
    Route::apiResource('paystation', App\Http\Controllers\API\PayStationController::class);
    Route::apiResource('voucher', App\Http\Controllers\API\VoucherController::class);
    Route::apiResource('category', App\Http\Controllers\API\CategoryController::class);
    Route::apiResource('user', App\Http\Controllers\API\UserController::class);
    Route::apiResource('variant', App\Http\Controllers\API\VariantController::class);

    Route::prefix('tavsir')->group(function () {
        Route::post('/product', [App\Http\Controllers\API\TavsirController::class,'Product']);
        Route::get('/category', [App\Http\Controllers\API\TavsirController::class,'Category']);
        Route::get('/countnewtng', [App\Http\Controllers\API\TavsirController::class,'CountNewTNG']);
        Route::post('/cartorder', [App\Http\Controllers\API\TavsirController::class,'CartOrder']);
    });
});
Route::post('/login', [App\Http\Controllers\API\AuthController::class,'login']);

Route::prefix('travshop')->group(function () {
    Route::post('/rest-area', [App\Http\Controllers\API\TravShopController::class,'restArea']);
    Route::post('/tenant', [App\Http\Controllers\API\TravShopController::class,'tenant']);
    Route::post('/product', [App\Http\Controllers\API\TravShopController::class,'product']);
    Route::get('/product/{id}', [App\Http\Controllers\API\TravShopController::class,'productById']);
    Route::post('/order', [App\Http\Controllers\API\TravShopController::class,'order']);
    Route::get('/order/{id}', [App\Http\Controllers\API\TravShopController::class,'orderById']);
    Route::post('/order-confirmation/{id}', [App\Http\Controllers\API\TravShopController::class,'orderConfirm']);
    Route::get('/payment-method', [App\Http\Controllers\API\TravShopController::class,'paymentMethod']);
});