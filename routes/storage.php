<?php

declare(strict_types=1);

use App\Http\Controllers\PublicFileController;
use Illuminate\Support\Facades\Route;

Route::get('storage/{path}', PublicFileController::class)->where('path', '.*')->name('public-file');
