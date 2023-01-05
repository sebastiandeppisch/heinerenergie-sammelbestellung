<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrderController;

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

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->group(function () {
    Route::get('orderexport', [OrderController::class, 'export']);
    Route::get('bulkorders/{bulkorder}/orderexport', [OrderController::class, 'export']);
});

Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '^(?!api).*$');

Route::get('/change-password')->name('password.reset');