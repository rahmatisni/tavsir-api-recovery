<?php

use App\Services\Payment\MidtransService;
use App\Services\Payment\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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
    return response()->json(['error' => 'Udah kenapa ga usah nyoba terus. gue mau pulang!'], 404);
});

Route::post('/external/{vendor}/{apikey}', function(Request $request, $vendor, $apikey){
    if($apikey !== env('API_KEY_EXTERNAL', null)) {
        return response('401 unauthorized', 401);
    }
    $service = app(PaymentService::class);
    
    switch ($vendor) {
        case 'midtrans':
            return $service->midtransNotificationCallback($request->all());
            break;
        
        default:
            return response('400', 400);
            break;
    }
});
