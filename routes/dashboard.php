<?php

use App\Http\Controllers\API\CategoryTenantController;
use App\Http\Controllers\API\DashboardController;
use App\Http\Controllers\API\KiosBank\KiosBankController;
use App\Http\Controllers\API\PPOBDashboardController;
use App\Models\PgJmto;
use App\Services\External\KiosBankService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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

Route::prefix('ppob')->controller(PPOBDashboardController::class)->group(function () {
    Route::get('all-product','allProduct');
    Route::get('saldo-kios','saldoKiosbank');
    Route::get('transaction','transaction');
    Route::get('penjualan','penjualan');
    Route::get('pendapatan','pendapatan');
});