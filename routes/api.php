<?php

use App\Http\Controllers\AdviceController;
use App\Http\Controllers\AdviceStatusController;
use App\Http\Controllers\AdviceTypeController;
use App\Http\Controllers\Api\GroupUserController;
use App\Http\Controllers\GeoSearchController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\StoreAdviceController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\UserController;
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

require_once __DIR__.'/api.auth.php';

Route::middleware('auth')->group(function () {
    Route::resource('users', UserController::class);
    Route::resource('advices', AdviceController::class);
    Route::resource('advicestatus', AdviceStatusController::class);

    Route::apiResource('groups.users', GroupUserController::class);

    Route::post('advices/{advice}/advisors', [AdviceController::class, 'setAdvisors']);

    Route::apiResource('settings', SettingController::class)->except(['store', 'destroy']);

    Route::post('upload', UploadController::class);
    Route::post('profile/picture', [UserController::class, 'picture']);

    Route::post('profile/address', [UserController::class, 'address']);
    Route::post('advices/{advice}/assign', [AdviceController::class, 'assign']);
    Route::get('advices/{advice}/advisors', [AdviceController::class, 'sortedAdvisors']);

    Route::get('html/advisorInfo', [SettingController::class, 'advisorInfo']);

    Route::get('map/search', GeoSearchController::class);
});

Route::get('html/impress', [SettingController::class, 'impress']);
Route::get('html/datapolicy', [SettingController::class, 'datapolicy']);

Route::resource('advicetypes', AdviceTypeController::class)->only(['index', 'show']);

Route::post('newadvice', StoreAdviceController::class);
