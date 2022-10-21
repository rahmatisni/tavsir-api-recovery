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
    Route::post('/pin', [App\Http\Controllers\API\AuthController::class, 'pinStore']);
    Route::post('/reset-pin', [App\Http\Controllers\API\AuthController::class, 'resetPin']);
    Route::post('/open-cashier', [App\Http\Controllers\API\AuthController::class, 'openCashier']);
    Route::post('/check-open-cashier', [App\Http\Controllers\API\AuthController::class, 'checkOpenCashier']);
    Route::post('/close-cashier', [App\Http\Controllers\API\AuthController::class, 'closeCashier']);
    Route::post('/dashboard', [App\Http\Controllers\API\DashboardController::class, 'index']);

    Route::post('/logout', [App\Http\Controllers\API\AuthController::class,'logout']);
    Route::post('/rest-area/update-status', [App\Http\Controllers\API\RestAreaController::class,'updateStatus']);
    Route::apiResource('rest-area', App\Http\Controllers\API\RestAreaController::class);
    Route::apiResource('business', App\Http\Controllers\API\BusinessController::class);
    Route::apiResource('tenant', App\Http\Controllers\API\TenantController::class);
    Route::post('/product/update-status', [App\Http\Controllers\API\ProductController::class,'updateStatus']);
    Route::apiResource('product', App\Http\Controllers\API\ProductController::class);
    Route::apiResource('payment-method', App\Http\Controllers\API\PaymentMethodController::class);
    Route::apiResource('paystation', App\Http\Controllers\API\PaystationController::class);
    Route::apiResource('voucher', App\Http\Controllers\API\VoucherController::class);
    Route::apiResource('category', App\Http\Controllers\API\CategoryController::class);
    Route::post('/user/approve-reset-pin/{id}', [App\Http\Controllers\API\UserController::class,'approveResetPin']);
    Route::post('/user/reject-reset-pin/{id}', [App\Http\Controllers\API\UserController::class,'rejectResetPin']);
    Route::apiResource('user', App\Http\Controllers\API\UserController::class);
    Route::apiResource('ruas', App\Http\Controllers\API\RuasController::class);
    Route::get('/rekap-pendapatan', [App\Http\Controllers\API\RekapPendapatanController::class, 'index']);
    Route::get('/laporan-rekap-transaksi', [App\Http\Controllers\API\LaporanRekapTransaksiController::class, 'index']);
    Route::get('/laporan-rekap-transaksi/rekap/{id}', [App\Http\Controllers\API\LaporanRekapTransaksiController::class, 'showRekap']);
    Route::post('/laporan-rekap-transaksi/transaksi/{id}', [App\Http\Controllers\API\LaporanRekapTransaksiController::class, 'showTransaksi']);
    Route::get('/laporan-rekap-transaksi/download/{id}', [App\Http\Controllers\API\LaporanRekapTransaksiController::class, 'download']);
    Route::post('/laporan/penjualan', [App\Http\Controllers\API\LaporanController::class, 'downloadLaporanPenjualan']);
    Route::post('/laporan/operational', [App\Http\Controllers\API\LaporanController::class, 'downloadLaporanOperational']);
    Route::post('/laporan/penjualan-kategori', [App\Http\Controllers\API\LaporanController::class, 'downloadLaporanPenjualanKategori']);
    Route::post('/laporan/metode-pembayaran', [App\Http\Controllers\API\LaporanController::class, 'downloadLaporanMetodePembayaran']);
    Route::post('/laporan/invoice', [App\Http\Controllers\API\LaporanController::class, 'downloadLaporanInvoice']);
    Route::post('/laporan/transaksi', [App\Http\Controllers\API\LaporanController::class, 'downloadLaporanTransaksi']);

    Route::prefix('tavsir')->group(function () {
        Route::get('/product', [App\Http\Controllers\API\TavsirController::class,'productList']);
        Route::post('/send-notif', [App\Http\Controllers\API\TavsirController::class,'sendNotif']);
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
        Route::post('/cart-delete', [App\Http\Controllers\API\TavsirController::class,'CartDelete']);
        Route::get('/count-cart-saved', [App\Http\Controllers\API\TavsirController::class,'CountCarSaved']);
        Route::get('/order/{id}', [App\Http\Controllers\API\TavsirController::class,'OrderById']);
        Route::get('/order-list', [App\Http\Controllers\API\TavsirController::class,'OrderList']);
        Route::post('/order', [App\Http\Controllers\API\TavsirController::class,'Order']);
        Route::post('/order-confirmation/{id}', [App\Http\Controllers\API\TavsirController::class,'orderConfirm']);
        Route::get('/payment-method', [App\Http\Controllers\API\TavsirController::class,'PaymentMethod']);
        Route::post('/payment-order', [App\Http\Controllers\API\TavsirController::class,'PaymentOrder']);
        Route::apiResource('customize', App\Http\Controllers\API\CustomizeController::class);
        Route::post('/order-change-status/{id}', [App\Http\Controllers\API\TavsirController::class,'changeStatusOrder']);
        Route::get('/invoice', [App\Http\Controllers\API\InvoiceController::class,'index']);
        Route::get('/invoice/{id}', [App\Http\Controllers\API\InvoiceController::class,'show']);
        Route::post('/invoice', [App\Http\Controllers\API\InvoiceController::class,'store']);
        Route::post('/invoice-paid/{id}', [App\Http\Controllers\API\InvoiceController::class,'paid']);

        Route::post('/subscription/{id}', [App\Http\Controllers\API\SubscriptionController::class,'changeStatus']);
        Route::apiResource('subscription', App\Http\Controllers\API\SubscriptionController::class);

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
    Route::post('/rating/{id}', [App\Http\Controllers\API\RatingController::class, 'store']);
    Route::post('/order-verification/{id}', [App\Http\Controllers\API\TravShopController::class,'verifikasiOrder']);
});
Route::get('chat', [App\Http\Controllers\API\ChatController::class, 'index']);
Route::get('chat/{chat}', [App\Http\Controllers\API\ChatController::class, 'show']);
Route::post('chat', [App\Http\Controllers\API\ChatController::class, 'store']);
Route::post('chat/{chat}', [App\Http\Controllers\API\ChatController::class, 'read']);

Route::post('/send-email/{order}', [App\Http\Controllers\API\SendController::class, 'mail']);

Route::post('payment-gateway/sof/list', [App\Http\Controllers\API\PaymentGatewayController::class, 'sofList']);
Route::post('payment-gateway/va/create', [App\Http\Controllers\API\PaymentGatewayController::class, 'vaCreate']);
Route::post('payment-gateway/va/cekstatus', [App\Http\Controllers\API\PaymentGatewayController::class, 'vaStatus']);
Route::post('payment-gateway/va/delete', [App\Http\Controllers\API\PaymentGatewayController::class, 'vaDelete']);


Route::get('/pg-cek', function(Request $request){
    if($request->sof_id && $request->payment_method_id )
    {
        return PgJmto::tarifFee($request->sof_id, $request->payment_method_id,$request->sub_merchant_id );
    }
    $payload = [
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
    ];
    return PgJmto::vaCreate($payload['sof_code'], $payload['bill_id'], $payload['bill_name'], $payload['amount'], $payload['desc'], $payload['phone'], $payload['email'], $payload['customer_name']);
});

Route::get('/pg-tarif', function(){
    $mandiri_va = PgJmto::feeMandiriVa();
    $bri_va = PgJmto::feeBriVa();
    $bni_va = PgJmto::feeBniVa();
    return [
        'bri_va' => $bri_va,
        'mandiri_va' => $mandiri_va,
        'bni_va' => $bni_va
    ];
});
