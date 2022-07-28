<?php

use App\Models\PgJmto;
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
        Route::post('/cart-order', [App\Http\Controllers\API\TavsirController::class,'CartOrder']);
        Route::post('/cart-delete', [App\Http\Controllers\API\TavsirController::class,'CartDelete']);
        Route::get('/count-cart-saved', [App\Http\Controllers\API\TavsirController::class,'CountCarSaved']);
        Route::get('/cart-saved', [App\Http\Controllers\API\TavsirController::class,'cartSaved']);
        Route::get('/cart/{id}', [App\Http\Controllers\API\TavsirController::class,'CartById']);
        Route::post('/order', [App\Http\Controllers\API\TavsirController::class,'Order']);
        Route::get('/order/{id}', [App\Http\Controllers\API\TavsirController::class,'OrderById']);
        Route::post('/order-confirmation/{id}', [App\Http\Controllers\API\TravShopController::class,'orderConfirm']);
        Route::get('/payment-method', [App\Http\Controllers\API\TavsirController::class,'PaymentMethod']);
        Route::post('/payment-order', [App\Http\Controllers\API\TavsirController::class,'PaymentOrder']);
    });

    Route::prefix('tavsir/tng')->group(function () {
        Route::post('/tenant-order', [App\Http\Controllers\API\TavsirTnGController::class,'TenantOrder']);  
        Route::post('/order-ready/{id}', [App\Http\Controllers\API\TavsirTnGController::class,'OrderReady']);  
        Route::post('/order-verif/{id}', [App\Http\Controllers\API\TavsirTnGController::class,'OrderVerification']);  
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
    Route::get('/order-customer/{id}', [App\Http\Controllers\API\TravShopController::class,'orderCustomer']);
    Route::post('/order-confirmation/{id}', [App\Http\Controllers\API\TravShopController::class,'orderConfirm']);
    Route::get('/payment-method', [App\Http\Controllers\API\TravShopController::class,'paymentMethod']);
    Route::post('/create-payment/{id}', [App\Http\Controllers\API\TravShopController::class,'createPayment']);
    Route::get('/payment-order/{id}', [App\Http\Controllers\API\TravShopController::class,'paymentByOrderId']);
    Route::get('/payment-status/{id}', [App\Http\Controllers\API\TravShopController::class,'statusPayment']);
});



Route::get('/pg-cek', function(){
    $payload = [
        'sof_id' => 1
    ];
    return PgJmto::service('POST','/sof/list',$payload);
});