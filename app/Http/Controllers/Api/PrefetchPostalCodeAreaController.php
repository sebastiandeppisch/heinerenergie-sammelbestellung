<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PrefetchPostalCodeAreaRequest;
use App\Jobs\PrefetchPostalCodePolygons;
use Illuminate\Http\Response;

/**
 * Warms the polygon cache for one postal code while the user is still typing.
 */
class PrefetchPostalCodeAreaController extends Controller
{
    public function __invoke(PrefetchPostalCodeAreaRequest $request): Response
    {
        PrefetchPostalCodePolygons::dispatch($request->string('postal_code')->toString());

        return response()->noContent(Response::HTTP_ACCEPTED);
    }
}
