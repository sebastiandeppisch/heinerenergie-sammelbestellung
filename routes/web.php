<?php

use App\Http\Controllers\BulkOrder;
use App\Http\Controllers\BulkOrderController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PageController;
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

Route::get('/', BulkOrder::class);

Route::middleware('auth')->group(function () {
    Route::get('bulkorders/{bulkorder}/orderexport', [OrderController::class, 'export'])->name('orderexport');
});

/*Route::get('/{any}', function () {
    return view('app');
})->where('any', '^(?!api).*$');*/

Route::get('/change-password')->name('password.reset');
Route::get('/login-form', [PageController::class, 'login'])->name('login');

Route::get('/backend', function () {
    return redirect()->route('dashboard');
})->name('backend');

Route::get('/dashboard', [PageController::class, 'dashboard'])->name('dashboard');