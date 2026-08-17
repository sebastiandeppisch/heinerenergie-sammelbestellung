<?php

use App\Http\Controllers\AdviceController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\DevLoginController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\FormDefinitionController;
use App\Http\Controllers\FormSubmissionController;
use App\Http\Controllers\FormSubmitController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\Groups\GroupUserController;
use App\Http\Controllers\MailAccountController;
use App\Http\Controllers\MapEmbedController;
use App\Http\Controllers\MapPointCategoryController;
use App\Http\Controllers\MapPointController;
use App\Http\Controllers\NextcloudGroupController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\SystemAdminController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\CheckSysAdmin;
use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
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
    Route::resource('users', UserController::class)->only(['index', 'store', 'update']);
    Route::get('/settings', [PageController::class, 'settings'])->name('settings');
    Route::get('/advices', [PageController::class, 'advices'])->name('advices');
    Route::get('/advices/{advice}', [PageController::class, 'showAdvice'])->name('advices.show');

    Route::delete('advices/{advice}', [AdviceController::class, 'delete'])->name('advices.delete');

    Route::post('/advices/{advice}/comments', [AdviceController::class, 'storeComment'])->name('advices.comment.store');
    Route::get('/advicesmap', [PageController::class, 'advicesMap'])->name('advices.map');
    Route::get('/advices', [AdviceController::class, 'index'])->name('advices');
    Route::get('/advices/{advice}', [AdviceController::class, 'show'])->name('advices.show');
    Route::get('/advicesmap', [AdviceController::class, 'map'])->name('advices.map');
    Route::get('/backend', fn () => redirect()->route('dashboard'))->middleware('auth')->name('backend');

    Route::resource('groups', GroupController::class);
    Route::resource('groups.users', GroupUserController::class);

    Route::post('actAsGroup/{group}', [UserController::class, 'actAsGroup'])->name('actAsGroup');
    Route::post('actAsSystemAdmin', [UserController::class, 'actAsSystemAdmin'])->name('actAsSystemAdmin');

    Route::post('/groups/{group}/consulting-area', [GroupController::class, 'updateConsultingArea'])
        ->name('groups.consulting-area.update');

    Route::delete('/groups/{group}/consulting-area', [GroupController::class, 'deleteConsultingArea'])
        ->name('groups.consulting-area.delete');

    Route::post('/advices', [AdviceController::class, 'store'])->name('advices.store');
    Route::put('advices/{advice}', [AdviceController::class, 'update'])->name('advices.update');
    Route::put('advices/{advice}/status', [AdviceController::class, 'updateStatus'])->name('advices.updateStatus');
    Route::put('advices/{advice}/advisor', [AdviceController::class, 'updateAdvisor'])->name('advices.updateAdvisor');
    Route::put('/groups/{group}/dashboard-info', [GroupController::class, 'updateDashboardInfo'])
        ->name('groups.dashboard-info.update');

    Route::put('/groups/{group}/new-advice-mail', [GroupController::class, 'updateNewAdviceMail'])
        ->name('groups.new-advice-mail.update');

    Route::get('/groups/{group}/nextcloud', [NextcloudGroupController::class, 'index'])
        ->name('groups.nextcloud');
    Route::post('/groups/{group}/nextcloud/{ncUser}/import', [NextcloudGroupController::class, 'import'])
        ->name('groups.nextcloud.import');
    Route::post('/groups/{group}/nextcloud/{ncUser}/add-to-group', [NextcloudGroupController::class, 'addToGroup'])
        ->name('groups.nextcloud.add-to-group');

    Route::post('advices/{advice}/assign', [AdviceController::class, 'assign'])->name('advices.assign');
    Route::post('advices/{advice}/unassign', [AdviceController::class, 'unassign'])->name('advices.unassign');
    Route::post('advices/{advice}/transfer', [AdviceController::class, 'transfer'])->name('advices.transfer');

    Route::post('advices/{advice}/checklist-entries', [AdviceController::class, 'storeChecklistEntry'])->name('checklist-entries.store');
    Route::put('advices/{advice}/checklist-entries/{checklistEntry}', [AdviceController::class, 'updateChecklistEntry'])->name('checklist-entries.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');

    Route::resource('form-definitions', FormDefinitionController::class);
    Route::post('form-definitions/from-template', [FormDefinitionController::class, 'storeFromTemplate'])
        ->name('form-definitions.from-template');

    Route::resource('form-submissions', FormSubmissionController::class)->only(['index']);
    Route::post('form-submissions/{formSubmission}/mark-seen', [FormSubmissionController::class, 'markSeen'])
        ->name('form-submissions.mark-seen');
    Route::post('form-submissions/{formSubmission}/mark-unseen', [FormSubmissionController::class, 'markUnseen'])
        ->name('form-submissions.mark-unseen');

    Route::get('mappoints-map', [MapPointController::class, 'map'])->name('map-points-map');

    Route::resource('mappoints', MapPointController::class);
    Route::resource('mappoint-categories', MapPointCategoryController::class);
    Route::resource('map-embeds', MapEmbedController::class);

    // Mail account page (no enc_key required – user can always view setup)
    Route::get('/mail/account', [MailAccountController::class, 'show'])->name('mail.account.show');
    // Mail routes that do not require the encryption key
    Route::post('/mail/discover', [MailAccountController::class, 'discover'])->name('mail.discover');

    // Mail operations that require the encryption key cookie
    Route::middleware('enc_key')->group(function () {
        Route::post('/mail/account', [MailAccountController::class, 'store'])->name('mail.account.store');
        Route::delete('/mail/account', [MailAccountController::class, 'destroy'])->name('mail.account.destroy');
    });

    // System Admin Routes
    Route::middleware(CheckSysAdmin::class)->group(function () {
        Route::get('/system-admin', [SystemAdminController::class, 'index'])->name('system-admin');
        Route::post('/system-admin/migrate', [SystemAdminController::class, 'migrate'])->name('system-admin.migrate');
        Route::post('/system-admin/seed', [SystemAdminController::class, 'seed'])->name('system-admin.seed');
    });
});

Route::get('/change-password', [PageController::class, 'changePassword'])->name('password.reset');
Route::post('/change-password', [NewPasswordController::class, 'store'])->name('change-password.store');

Route::get('/', fn () => redirect()->route('dashboard'))->name('home');

Route::get('/login-form', [PageController::class, 'login'])->name('login');
Route::post('/login-form', [AuthenticatedSessionController::class, 'store'])->name('login.store');
Route::get('/register', [RegisterController::class, 'show'])->name('register');
Route::post('/register', [RegisterController::class, 'store'])->name('register.store');
Route::get('/reset-password', [PageController::class, 'resetPassword'])->name('reset-password');
Route::post('/reset-password', [PasswordResetLinkController::class, 'store'])->name('forgot-password');
Route::get('newadvice', [PageController::class, 'newAdvice'])->name('newadvice');
Route::get('impress', [PageController::class, 'impress'])->name('impress');
Route::get('datapolicy', [PageController::class, 'datapolicy'])->name('datapolicy');

if (app()->environment('local')) {
    Route::get('/dev-login/{user}', [DevLoginController::class, 'login'])->name('dev.login');
}

Route::get('/forms/{formDefinition}', [FormSubmitController::class, 'show'])
    ->name('form.show');
Route::post('/forms/{formDefinition}', [FormSubmitController::class, 'submit'])
    ->name('form.submit')
    ->withoutMiddleware(PreventRequestForgery::class)
    ->middleware([HandlePrecognitiveRequests::class, 'throttle:form-submit']);

Route::get('/map/{mapEmbed}', [MapPointController::class, 'publicMap'])
    ->name('map.public');

Route::put('/users/{user}/password', [UserController::class, 'changePassword'])
    ->name('users.changePassword');
