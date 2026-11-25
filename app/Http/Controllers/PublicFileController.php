<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves files of the public disk when it lives in a bucket without a public address, e.g. on Laravel Cloud.
 * Locally the web server answers /storage from the symlink before this route is reached.
 */
class PublicFileController extends Controller
{
    public function __invoke(string $path): StreamedResponse
    {
        $disk = Storage::disk('public');

        abort_if(str_contains($path, '..') || ! $disk->exists($path), 404);

        return $disk->response($path, headers: ['Cache-Control' => 'public, max-age=86400']);
    }
}
