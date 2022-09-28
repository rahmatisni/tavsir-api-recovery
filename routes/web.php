<?php

use App\Events\StatusOrderEvent;
use App\Models\TransOrder;
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
    return view('welcome');
});



Route::get('/cek', function () {
    $trans_order = TransOrder::first();
    $cek = StatusOrderEvent::dispatch($trans_order);
    return $cek;
});