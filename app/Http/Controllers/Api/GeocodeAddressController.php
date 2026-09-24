<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\FetchCoordinateByAddress;
use App\Http\Controllers\Controller;
use App\Http\Requests\GeocodeAddressRequest;
use Illuminate\Http\JsonResponse;

/**
 * Resolves an address to coordinates on demand.
 *
 * Deliberately server side: it keeps the shared cache and the rate limit
 * towards OpenStreetMap in one place, instead of letting every browser query
 * Nominatim directly with the visitor's own IP address.
 */
class GeocodeAddressController extends Controller
{
    public function __invoke(GeocodeAddressRequest $request): JsonResponse
    {
        $coordinate = app(FetchCoordinateByAddress::class)($request->address());

        return response()->json(['coordinate' => $coordinate]);
    }
}
