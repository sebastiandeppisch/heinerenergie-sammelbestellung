<?php

use App\Http\Controllers\Api\AdviceController;
use App\Http\Controllers\Api\AdviceStatusController;
use App\Http\Controllers\Api\AdviceTypeController;
use App\Http\Controllers\Api\GeoSearchController;
use App\Http\Controllers\Api\GroupAdviceStatusController;
use App\Http\Controllers\Api\KpiController;
use App\Http\Controllers\Api\MailController;
use App\Http\Controllers\Api\NextcloudAdviceController;
use App\Http\Controllers\Api\ReverseGeoSearchController;
use App\Http\Controllers\Api\SettingController;
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

Route::middleware('auth')->group(function () {
    Route::get('kpi/status-distribution', [KpiController::class, 'statusDistribution'])->name('api.kpi.status-distribution');
    Route::get('kpi/monthly-count', [KpiController::class, 'monthlyCount'])->name('api.kpi.monthly-count');
    Route::get('kpi/current-status-distribution', [KpiController::class, 'currentStatusDistribution'])->name('api.kpi.current-status-distribution');

    Route::resource('users', UserController::class)->only(['index', 'show'])->names('api.users');
    Route::resource('advices', AdviceController::class)->except(['index', 'store'])->names('api.advices');

    Route::post('advices/{advice}/advisors', [AdviceController::class, 'setAdvisors']);

    Route::apiResource('settings', SettingController::class)->except(['store', 'destroy', 'index']);

    Route::post('upload', UploadController::class);
    Route::post('profile/picture', [UserController::class, 'picture']);

    Route::post('profile/address', [UserController::class, 'address']);
    Route::post('advices/{advice}/assign', [AdviceController::class, 'assign']);
    Route::get('advices/{advice}/advisors', [AdviceController::class, 'sortedAdvisors']);

    Route::get('map/search', GeoSearchController::class);
    Route::get('map/reverse-search', ReverseGeoSearchController::class)->name('api.map.reverse-search');

    Route::prefix('advices/{advice}/nextcloud')->group(function () {
        Route::get('search', [NextcloudAdviceController::class, 'search'])->name('api.nextcloud.search');
        Route::get('browse', [NextcloudAdviceController::class, 'browse'])->name('api.nextcloud.browse');
        Route::post('folder', [NextcloudAdviceController::class, 'createFolder'])->name('api.nextcloud.createFolder');
        Route::post('link', [NextcloudAdviceController::class, 'link'])->name('api.nextcloud.link');
        Route::delete('link', [NextcloudAdviceController::class, 'unlink'])->name('api.nextcloud.unlink');
        Route::post('upload', [NextcloudAdviceController::class, 'upload'])->name('api.nextcloud.upload');
        Route::get('download', [NextcloudAdviceController::class, 'download'])->name('api.nextcloud.download');
        Route::get('files', [NextcloudAdviceController::class, 'files'])->name('api.nextcloud.files');
    });

    Route::apiResource('groups.advicestatus', GroupAdviceStatusController::class);

    Route::apiResource('advicestatus', AdviceStatusController::class)->only(['index', 'show']);

    Route::middleware('enc_key')->prefix('advices/{advice}/mails')->group(function () {
        Route::get('/', [MailController::class, 'index'])->name('api.mail.index');
        Route::post('/', [MailController::class, 'store'])->name('api.mail.store');
        Route::get('{folder}/{uid}', [MailController::class, 'show'])->name('api.mail.show')
            ->where('folder', '[^/]+');
    });
});

Route::resource('advicetypes', AdviceTypeController::class)->only(['index', 'show']);
