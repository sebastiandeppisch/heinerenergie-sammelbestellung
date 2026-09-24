<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Data\AdviceData;
use App\Enums\GeocodingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\SetAdviceCoordinateRequest;
use App\Models\Advice;
use App\ValueObjects\Coordinate;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Places the pin of an advice by hand.
 *
 * This is the way out when OpenStreetMap does not know the address or was
 * unreachable. The status moves to manual, which stops the geocoding job from
 * ever overwriting what the user placed.
 */
class SetAdviceCoordinateController extends Controller
{
    public function __invoke(SetAdviceCoordinateRequest $request, Advice $advice): JsonResponse
    {
        $advice->coordinate = new Coordinate(
            lat: (float) $request->validated('lat'),
            lng: (float) $request->validated('lng'),
        );
        $advice->geocoding_status = GeocodingStatus::MANUAL;

        // Quietly, so this does not start another round of geocoding for an
        // advice that now has exactly the position the user wants.
        $advice->saveQuietly();

        return response()->json(AdviceData::fromModel($advice, Auth::user()));
    }
}
