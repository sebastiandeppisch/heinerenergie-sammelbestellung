<?php

use App\Http\Controllers\Api\AdviceController;
use App\Http\Controllers\Api\AdviceStatusController;
use App\Http\Controllers\Api\AdviceTypeController;
use App\Http\Controllers\Api\GeoSearchController;
use App\Http\Controllers\Api\GroupAdviceStatusController;
use App\Http\Controllers\Api\GroupUserController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\StoreAdviceController;
use App\Http\Controllers\Api\UploadController;
use App\Http\Controllers\Api\UserController;
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
    Route::resource('users', UserController::class)->only(['index', 'show']);
    Route::resource('advices', AdviceController::class)->except(['index'])->names('api.advices');

    Route::apiResource('groups.users', GroupUserController::class);

    Route::post('advices/{advice}/advisors', [AdviceController::class, 'setAdvisors']);

    Route::apiResource('settings', SettingController::class)->except(['store', 'destroy', 'index']);

    Route::post('upload', UploadController::class);
    Route::post('profile/picture', [UserController::class, 'picture']);

    Route::post('profile/address', [UserController::class, 'address']);
    Route::post('advices/{advice}/assign', [AdviceController::class, 'assign']);
    Route::get('advices/{advice}/advisors', [AdviceController::class, 'sortedAdvisors']);

    Route::get('map/search', GeoSearchController::class);

    Route::apiResource('groups.advicestatus', GroupAdviceStatusController::class);

    Route::apiResource('advicestatus', AdviceStatusController::class)->only(['index', 'show']);
});

Route::resource('advicetypes', AdviceTypeController::class)->only(['index', 'show']);

Route::post('newadvice', StoreAdviceController::class)->name('api.newadvice');
