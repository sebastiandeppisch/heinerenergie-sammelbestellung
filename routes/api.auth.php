<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use Illuminate\Support\Facades\Route;

// Route::middleware('guest')->group(function () {
Route::post('login', [AuthenticatedSessionController::class, 'store']);

Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
    ->name('password.email');

Route::post('reset-password', [NewPasswordController::class, 'store'])
    ->name('password.update');
// });

Route::get('login', [AuthenticatedSessionController::class, 'index']);
