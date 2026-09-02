<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\BuildPolygonFromPostalCodes;
use App\Http\Controllers\Controller;
use App\Http\Requests\BuildConsultingAreaFromPostalCodesRequest;
use App\Models\Group;
use Illuminate\Http\JsonResponse;

class PostalCodeAreaController extends Controller
{
    public function __invoke(BuildConsultingAreaFromPostalCodesRequest $request, Group $group): JsonResponse
    {
        /** @var array<int, string> $postalCodes */
        $postalCodes = $request->validated('postal_codes');

        $polygon = app(BuildPolygonFromPostalCodes::class)($postalCodes);

        return response()->json(['polygon' => $polygon]);
    }
}
