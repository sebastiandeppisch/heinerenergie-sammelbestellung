<?php

use App\Http\Controllers\AdviceController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\DevLoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\FormDefinitionController;
use App\Http\Controllers\FormSubmissionController;
use App\Http\Controllers\FormSubmitController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\MapPointCategoryController;
use App\Http\Controllers\MapPointController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\UserController;
use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
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

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [PageController::class, 'dashboard'])->name('dashboard');
    Route::get('/initiatives/select', [PageController::class, 'initiativeSelection'])->name('initiatives.select');
    Route::get('/profile', [PageController::class, 'profile'])->name('profile');
    Route::get('/users', [PageController::class, 'users'])->name('users');
    Route::get('/settings', [PageController::class, 'settings'])->name('settings');
    Route::get('/advices', [PageController::class, 'advices'])->name('advices');
    Route::get('/advices/{advice}', [PageController::class, 'showAdvice'])->name('advices.show');
    Route::post('/advices/{advice}/comments', [AdviceController::class, 'storeComment'])->name('advices.comment.store');
    Route::get('/advicesmap', [PageController::class, 'advicesMap'])->name('advices.map');
    Route::get('/advices', [AdviceController::class, 'index'])->name('advices');
    Route::get('/advices/{advice}', [AdviceController::class, 'show'])->name('advices.show');
    Route::get('/advicesmap', [AdviceController::class, 'map'])->name('advices.map');
    Route::get('/backend', fn () => redirect()->route('dashboard'))->middleware('auth')->name('backend');

    Route::resource('groups', GroupController::class);

    Route::post('actAsGroup/{group}', [UserController::class, 'actAsGroup'])->name('actAsGroup');
    Route::post('actAsSystemAdmin', [UserController::class, 'actAsSystemAdmin'])->name('actAsSystemAdmin');

    Route::post('/groups/{group}/consulting-area', [GroupController::class, 'updateConsultingArea'])
        ->name('groups.consulting-area.update');

    Route::delete('/groups/{group}/consulting-area', [GroupController::class, 'deleteConsultingArea'])
        ->name('groups.consulting-area.delete');

    Route::post('advices/{advice}/unassign', [AdviceController::class, 'unassign'])->name('advices.unassign');
    Route::post('advices/{advice}/transfer', [AdviceController::class, 'transfer'])->name('advices.transfer');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');

    Route::resource('form-definitions', FormDefinitionController::class);

    Route::resource('form-submissions', FormSubmissionController::class)->only(['index']);
    Route::post('form-submissions/{formSubmission}/mark-seen', [FormSubmissionController::class, 'markSeen'])
        ->name('form-submissions.mark-seen');
    Route::post('form-submissions/{formSubmission}/mark-unseen', [FormSubmissionController::class, 'markUnseen'])
        ->name('form-submissions.mark-unseen');

    Route::get('mappoints-map', [MapPointController::class, 'map'])->name('map-points-map');

    Route::resource('mappoints', MapPointController::class);
    Route::resource('mappoint-categories', MapPointCategoryController::class);
});

Route::get('/change-password', [PageController::class, 'changePassword'])->name('password.reset');

Route::get('/', fn () => redirect()->route('dashboard'))->name('home');

Route::get('/login-form', [PageController::class, 'login'])->name('login');
Route::get('/register', [RegisterController::class, 'show'])->name('register');
Route::post('/register', [RegisterController::class, 'store'])->name('register.store');
Route::get('/reset-password', [PageController::class, 'resetPassword'])->name('reset-password');
Route::get('newadvice', [PageController::class, 'newAdvice'])->name('newadvice');
Route::get('impress', [PageController::class, 'impress'])->name('impress');
Route::get('datapolicy', [PageController::class, 'datapolicy'])->name('datapolicy');

if (app()->environment('local')) {
    Route::get('/dev-login/{user}', [DevLoginController::class, 'login'])->name('dev.login');
}

Route::get('/forms/{formDefinition}', [FormSubmitController::class, 'show'])
    ->name('form.show');
Route::post('/forms/{formDefinition}', [FormSubmitController::class, 'submit'])
    ->name('form.submit')->middleware([HandlePrecognitiveRequests::class]);

Route::get('/map', [MapPointController::class, 'publicMap'])
    ->name('map.public');
