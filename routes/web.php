<?php

use App\Http\Controllers\GroupController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\UserController;
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
    Route::get('bulkorders/{bulkorder}/orderexport', [OrderController::class, 'export'])->name('orderexport');
    Route::get('/dashboard', [PageController::class, 'dashboard'])->name('dashboard');
    Route::get('/profile', [PageController::class, 'profile'])->name('profile');
    Route::get('/orders', [PageController::class, 'orders'])->name('orders');
    Route::get('/products', [PageController::class, 'products'])->name('products');
    Route::get('/users', [PageController::class, 'users'])->name('users');
    Route::get('/settings', [PageController::class, 'settings'])->name('settings');
    Route::get('/advices', [PageController::class, 'advices'])->name('advices');
    Route::get('/advices/{advice}', [PageController::class, 'showAdvice'])->name('advices.show');
    Route::get('/advicesmap', [PageController::class, 'advicesMap'])->name('advices.map');
    Route::get('neworder', [PageController::class, 'newOrder'])->name('neworder');
    Route::get('/backend', fn () => redirect()->route('dashboard'))->name('backend');

    Route::resource('groups', GroupController::class);

    Route::post('actAsGroup/{group}', [UserController::class, 'actAsGroup'])->name('actAsGroup');
    Route::post('actAsSystemAdmin', [UserController::class, 'actAsSystemAdmin'])->name('actAsSystemAdmin');
});

Route::get('/change-password', [PageController::class, 'changePassword'])->name('password.reset');

Route::get('/', fn () => redirect()->route('home'));

Route::get('/sammelbestellung', [PageController::class, 'publicNewOrder'])->name('home');
Route::get('/login-form', [PageController::class, 'login'])->name('login');
Route::get('/reset-password', [PageController::class, 'resetPassword'])->name('reset-password');
Route::get('newadvice', [PageController::class, 'newAdvice'])->name('newadvice');
Route::get('impress', [PageController::class, 'impress'])->name('impress');
Route::get('datapolicy', [PageController::class, 'datapolicy'])->name('datapolicy');
