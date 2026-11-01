<?php

declare(strict_types=1);

use App\HexNet\Http\Controllers\HexNetController;
use App\HexNet\Http\Controllers\HexNetPublicController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::post('hexnets/grid-preview', [HexNetController::class, 'gridPreview'])->name('hexnets.grid-preview');
    Route::resource('hexnets', HexNetController::class)->only(['index', 'create', 'store', 'edit', 'destroy'])->parameters(['hexnets' => 'hexNet']);
    Route::put('hexnets/{hexNet}/step/{step}', [HexNetController::class, 'updateStep'])->name('hexnets.update-step');
    Route::post('hexnets/{hexNet}/setup-categories', [HexNetController::class, 'setupCategories'])->name('hexnets.setup-categories');
    Route::post('hexnets/{hexNet}/setup-form', [HexNetController::class, 'setupForm'])->name('hexnets.setup-form');
});

Route::get('hexanetz/{slug}', [HexNetPublicController::class, 'show'])->name('hexnet.public');
Route::get('hexanetz/{slug}/stand', [HexNetPublicController::class, 'score'])->name('hexnet.score');
