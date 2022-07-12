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
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

});

Route::apiResource('rest-area', App\Http\Controllers\API\RestAreaController::class);
Route::apiResource('business', App\Http\Controllers\API\BusinessController::class);
Route::apiResource('tenant', App\Http\Controllers\API\TenantController::class);
Route::apiResource('product', App\Http\Controllers\API\ProductController::class);
Route::apiResource('payment-method', App\Http\Controllers\API\PaymentMethodController::class);