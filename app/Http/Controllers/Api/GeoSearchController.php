<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\FetchCoordinateByFreeText;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GeoSearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $text = $request->input('query');
        $coordinate = app(FetchCoordinateByFreeText::class)($text);

        return response()->json($coordinate);
    }
}
