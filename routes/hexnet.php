<?php

declare(strict_types=1);

use App\HexNet\Http\Controllers\HexNetContactController;
use App\HexNet\Http\Controllers\HexNetController;
use App\HexNet\Http\Controllers\HexNetModerationController;
use App\HexNet\Http\Controllers\HexNetPublicController;
use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::resource('hexnets', HexNetController::class)->only(['index', 'create', 'store', 'edit', 'destroy'])->parameters(['hexnets' => 'hexNet']);
    Route::put('hexnets/{hexNet}/step/{step}', [HexNetController::class, 'updateStep'])->name('hexnets.update-step');
    Route::post('hexnets/{hexNet}/setup-categories', [HexNetController::class, 'setupCategories'])->name('hexnets.setup-categories');
    Route::post('hexnets/{hexNet}/grid-preview', [HexNetController::class, 'gridPreview'])->name('hexnets.grid-preview');
    Route::post('hexnets/{hexNet}/setup-form', [HexNetController::class, 'setupForm'])->name('hexnets.setup-form');
});

Route::get('hexanetz/{slug}', [HexNetPublicController::class, 'show'])->name('hexnet.public');
Route::get('hexanetz/{slug}/stand', [HexNetPublicController::class, 'score'])->name('hexnet.score');
Route::get('hexanetz/{slug}/trittsteine/{mapPoint}/kontakt', [HexNetContactController::class, 'create'])
    ->middleware('throttle:map-contact')
    ->name('hexnet.contact.create');
Route::post('hexanetz/{slug}/trittsteine/{mapPoint}/kontakt', [HexNetContactController::class, 'store'])
    ->middleware([HandlePrecognitiveRequests::class, 'throttle:map-contact'])
    ->name('hexnet.contact.store');

Route::get('hexanetz/moderation/{hexNet}/{mapPoint}/{action}/{admin}', [HexNetModerationController::class, 'show'])
    ->middleware('throttle:60,1')
    ->name('hexnet.moderation.show');
Route::post('hexanetz/moderation/{hexNet}/{mapPoint}/{action}/{admin}', [HexNetModerationController::class, 'store'])
    ->middleware('throttle:60,1')
    ->name('hexnet.moderation.store');
