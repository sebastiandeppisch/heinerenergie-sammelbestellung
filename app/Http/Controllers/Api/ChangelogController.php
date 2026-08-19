<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\VersionService;
use Illuminate\Http\JsonResponse;

class ChangelogController extends Controller
{
    public function __invoke(VersionService $versionService): JsonResponse
    {
        return response()->json([
            'version' => $versionService->version(),
            'changelog' => $versionService->changelogHtml(),
        ]);
    }
}
