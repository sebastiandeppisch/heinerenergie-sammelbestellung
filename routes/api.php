<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\AdviceController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\BulkOrderController;
use App\Http\Controllers\GeoSearchController;
use App\Http\Controllers\OrderItemController;
use App\Http\Controllers\AdviceTypeController;
use App\Http\Controllers\StoreOrderController;
use App\Http\Controllers\StoreAdviceController;
use App\Http\Controllers\AdviceStatusController;
use App\Http\Controllers\ProductCategoryController;
use App\Http\Controllers\ProductDownloadController;
use App\Http\Controllers\Api\GroupUserController;

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

require_once __DIR__.'/api.auth.php';

Route::middleware('auth')->group(function () {
    Route::resource('orders', OrderController::class)->except(['index', 'store']);
    Route::resource('users', UserController::class);
    Route::resource('advices', AdviceController::class);
    Route::resource('advicestatus', AdviceStatusController::class);

    Route::apiResource('groups.users', GroupUserController::class);

    Route::post('advices/{advice}/advisors', [AdviceController::class, 'setAdvisors']);

    Route::scopeBindings()->group(function () {
        Route::resource('orders.orderitems', OrderItemController::class);
    });

    Route::get('validateeditorderform', [OrderController::class, 'validateEditOrderForm']);

    Route::apiResource('settings', SettingController::class)->except(['store', 'destroy']);

    Route::post('orders/{order}/check', [OrderController::class, 'setChecked']);
    Route::post('orders/{order}/uncheck', [OrderController::class, 'setUnchecked']);
    Route::post('orders/{order}/advisors', [OrderController::class, 'setAdvisors']);
    Route::post('orders/{order}/sendmail', [OrderController::class, 'sendMail']);

    Route::resource('bulkorders', BulkOrderController::class);
    Route::scopeBindings()->group(function () {
        Route::resource('bulkorders.products', ProductController::class);
        Route::resource('bulkorders.orders', OrderController::class);

    });

    Route::scopeBindings()->group(function () {
        Route::resource('bulkorders.productcategories', ProductCategoryController::class);
    });

    Route::resource('products', ProductController::class)->except(['index']);
    Route::scopeBindings()->group(function () {
        Route::resource('products.downloads', ProductDownloadController::class);
    });

    Route::post('upload', UploadController::class);
    Route::post('profile/picture', [UserController::class, 'picture']);

    Route::post('profile/address', [UserController::class, 'address']);
    Route::post('advices/{advice}/sendorderlink', [AdviceController::class, 'sendOrderLink']);
    Route::post('advices/{advice}/assign', [AdviceController::class, 'assign']);
    Route::get('advices/{advice}/advisors', [AdviceController::class, 'sortedAdvisors']);
    Route::get('advices/{advice}/mails', [AdviceController::class, 'mails']);

    Route::post('actAsAdmin', [UserController::class, 'actAsAdmin']);
    Route::post('stopActAsAdmin', [UserController::class, 'stopActAsAdmin']);

    Route::get('html/advisorInfo', [SettingController::class, 'advisorInfo']);

    Route::get('map/search', GeoSearchController::class);
});

//Route::middleware('guest')->group(function () {
Route::get('products', [ProductController::class, 'index']);
Route::get('productcategories', [ProductCategoryController::class, 'index']);

Route::post('orders', StoreOrderController::class);
Route::get('validateorderform', [OrderController::class, 'validateorderform']);
//});
Route::get('checkpassword', [OrderController::class, 'checkPassword']);

Route::get('html/impress', [SettingController::class, 'impress']);
Route::get('html/datapolicy', [SettingController::class, 'datapolicy']);
Route::get('html/orderFormText', [SettingController::class, 'orderFormText']);

Route::resource('advicetypes', AdviceTypeController::class)->only(['index', 'show']);

Route::post('newadvice', StoreAdviceController::class);
