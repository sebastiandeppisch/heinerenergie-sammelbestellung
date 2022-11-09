<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\AdviceController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\BulkOrderController;
use App\Http\Controllers\OrderItemController;
use App\Http\Controllers\AdviceStatusController;
use App\Http\Controllers\ProductCategoryController;
use App\Http\Controllers\ProductDownloadController;

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

require_once(__DIR__."/api.auth.php");

Route::middleware('auth')->group(function () {
    Route::resource('orders', OrderController::class);
    Route::resource('users', UserController::class);
    Route::resource('advices', AdviceController::class);
    Route::resource('advicestatus', AdviceStatusController::class);

    
    Route::scopeBindings()->group(function(){
        Route::resource('orders.orderitems', OrderItemController::class);
    });

    Route::get('validateeditorderform', [OrderController::class, 'validateEditOrderForm']);

    Route::get('export', [OrderController::class, 'export']);
    Route::apiResource('settings', SettingController::class)->except(['store', 'destroy']);

    Route::post('orders/{order}/check', [OrderController::class, 'setChecked']);
    Route::post('orders/{order}/uncheck', [OrderController::class, 'setUnchecked']);

    Route::resource('bulkorders', BulkOrderController::class);
    Route::scopeBindings()->group(function(){
        Route::resource('bulkorders.products', ProductController::class);
    });

    Route::scopeBindings()->group(function(){
        Route::resource('bulkorders.productcategories', ProductCategoryController::class);
    });

    Route::resource('products', ProductController::class);
    Route::scopeBindings()->group(function(){
        Route::resource('products.productdownloads', ProductDownloadController::class);
    });

    Route::post('upload', UploadController::class);
});

//Route::middleware('guest')->group(function () {
Route::get('products', [ProductController::class, 'index']);
Route::get('productcategories', [ProductCategoryController::class, 'index']);

Route::post('orders', [OrderController::class, 'store']);
Route::get('validateorderform', [OrderController::class, 'validateorderform']);
//});
Route::get('checkpassword', [OrderController::class, 'checkPassword']);

Route::get('html/impress', [SettingController::class, 'impress']);
Route::get('html/datapolicy', [SettingController::class, 'datapolicy']);
Route::get('html/orderFormText', [SettingController::class, 'orderFormText']);