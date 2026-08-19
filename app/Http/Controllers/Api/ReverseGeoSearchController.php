<?php

namespace App\Http\Controllers\Api;

use App\Actions\FetchAddressByCoordinate;
use App\Http\Controllers\Controller;
use App\ValueObjects\Coordinate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReverseGeoSearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $coordinate = new Coordinate(
            lat: (float) $request->input('lat'),
            lng: (float) $request->input('lng'),
        );

        $location = app(FetchAddressByCoordinate::class)($coordinate);

        return response()->json(['location' => $location]);
    }
}
