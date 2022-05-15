<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderItemController;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::resource('orders', OrderController::class);
Route::resource('products', ProductController::class);
Route::resource('products', ProductController::class);

Route::scopeBindings()->group(function(){
    Route::resource('orders.orderitems', OrderItemController::class);
});

Route::get('validateorderform', [OrderController::class, 'validateorderform']);
Route::get('validateeditorderform', [OrderController::class, 'validateEditOrderForm']);

