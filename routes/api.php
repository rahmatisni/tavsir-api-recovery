<?php

use App\Models\PgJmto;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
    Route::apiResource('chat', App\Http\Controllers\API\ChatController::class);
    Route::post('/rating/{id}', [App\Http\Controllers\API\RatingController::class, 'store']);

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
    Route::apiResource('ruas', App\Http\Controllers\API\RuasController::class);

    Route::prefix('tavsir')->group(function () {
        Route::get('/product', [App\Http\Controllers\API\TavsirController::class,'productList']);
        Route::get('/product/{id}', [App\Http\Controllers\API\TavsirController::class,'productShow']);
        Route::post('/product', [App\Http\Controllers\API\TavsirController::class,'productStore']);
        Route::put('/product/{id}', [App\Http\Controllers\API\TavsirController::class,'productUpdate']);
        Route::delete('/product/{id}', [App\Http\Controllers\API\TavsirController::class,'productDestroy']);
        Route::get('/category', [App\Http\Controllers\API\TavsirController::class,'categoryList']);
        Route::get('/category/{category}', [App\Http\Controllers\API\TavsirController::class,'categoryShow']);
        Route::post('/category', [App\Http\Controllers\API\TavsirController::class,'categoryStore']);
        Route::put('/category/{category}', [App\Http\Controllers\API\TavsirController::class,'categoryUpdate']);
        Route::delete('/category/{category}', [App\Http\Controllers\API\TavsirController::class,'categoryDestroy']);
        Route::get('/countnewtng', [App\Http\Controllers\API\TavsirController::class,'CountNewTNG']);
        Route::post('/cart-order', [App\Http\Controllers\API\TavsirController::class,'CartOrder']);
        Route::post('/cart-delete', [App\Http\Controllers\API\TavsirController::class,'CartDelete']);
        Route::get('/count-cart-saved', [App\Http\Controllers\API\TavsirController::class,'CountCarSaved']);
        Route::get('/cart-saved', [App\Http\Controllers\API\TavsirController::class,'cartSaved']);
        Route::get('/cart/{id}', [App\Http\Controllers\API\TavsirController::class,'CartById']);
        Route::post('/order', [App\Http\Controllers\API\TavsirController::class,'Order']);
        Route::get('/order/{id}', [App\Http\Controllers\API\TavsirController::class,'OrderById']);
        Route::post('/order-confirmation/{id}', [App\Http\Controllers\API\TavsirController::class,'orderConfirm']);
        Route::get('/payment-method', [App\Http\Controllers\API\TavsirController::class,'PaymentMethod']);
        Route::post('/payment-order', [App\Http\Controllers\API\TavsirController::class,'PaymentOrder']);
        Route::apiResource('customize', App\Http\Controllers\API\CustomizeController::class);
    });

    Route::prefix('tavsir/tng')->group(function () {
        Route::post('/tenant-order', [App\Http\Controllers\API\TavsirTnGController::class,'TenantOrder']);
        Route::post('/tenant-order/{id}', [App\Http\Controllers\API\TavsirTnGController::class,'TenantOrderDetail']);
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
    Route::post('/order-cancel/{id}', [App\Http\Controllers\API\TravShopController::class,'orderCancel']);
    Route::get('/payment-method', [App\Http\Controllers\API\TravShopController::class,'paymentMethod']);
    Route::post('/create-payment/{id}', [App\Http\Controllers\API\TravShopController::class,'createPayment']);
    Route::get('/payment-order/{id}', [App\Http\Controllers\API\TravShopController::class,'paymentByOrderId']);
    Route::get('/payment-status/{id}', [App\Http\Controllers\API\TravShopController::class,'statusPayment']);
    Route::post('/saldo', [App\Http\Controllers\API\TravShopController::class,'saldo']);

});

Route::get('/pg-cek', function(){
    $payload = [
        'method' => 'POST',
        'path' => '/va/create',
        'payload' => [
            "sof_code" => "BRI",
            "bill_id" => "TNG-20220816132356",
            "bill_name" => "Take N Go",
            "amount" => "7000",
            "desc" => "Rumah Talas",
            "exp_date" => Carbon::now()->addDay(1)->format('Y-m-d H:i:s'),
            "va_type" => "close",
            "phone" => "6285156903693",
            "email" => "rony.cetzl@gmail.com",
            "customer_name" => "travoy customer test",
            "submerchant_id" => "98"
        ]
    ];
    return Illuminate\Support\Facades\Http::withoutVerifying()->post('https://travoy.jasamarga.co.id:3000/pg-jmto',$payload)->json();
    $payload = [
        'sof_id' => 1
    ];
    return PgJmto::service('/sof/list',$payload);
});
