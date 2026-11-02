<?php

declare(strict_types=1);

namespace App\HexNet\Http\Controllers;

use App\Data\MapPointCharacteristicData;
use App\HexNet\Data\HexNetPublicData;
use App\HexNet\Models\HexNet;
use App\HexNet\Services\HexNetGridService;
use App\HexNet\Services\HexNetPointPresenter;
use App\Http\Controllers\Controller;
use App\Models\MapPointCharacteristic;
use App\Services\CurrentGroupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HexNetPublicController extends Controller
{
    public function __construct(private readonly HexNetGridService $gridService) {}

    public function show(Request $request, string $slug, HexNetPointPresenter $presenter): Response
    {
        $hexNet = $this->find($request, $slug);
        app(CurrentGroupService::class)->setGroup($hexNet->group);
        $counts = $this->gridService->counts($hexNet);

        return Inertia::render('HexNet/Public', [
            'hexNet' => HexNetPublicData::fromModel($hexNet),
            'cells' => $this->gridService->cellsGeoJson($hexNet),
            'counts' => (object) $counts,
            'stats' => $this->gridService->stats($hexNet, $counts),
            'points' => $presenter->presentAll($hexNet),
            'measures' => $this->gridService->measures($hexNet)->map(fn (MapPointCharacteristic $measure): MapPointCharacteristicData => MapPointCharacteristicData::fromModel($measure))->values(),
            'measureCounts' => (object) $this->gridService->measureCounts($hexNet),
        ]);
    }

    public function score(Request $request, string $slug): JsonResponse
    {
        $hexNet = $this->find($request, $slug);
        $counts = $this->gridService->counts($hexNet);

        return response()->json([
            'counts' => (object) $counts,
            'stats' => $this->gridService->stats($hexNet, $counts),
        ]);
    }

    private function find(Request $request, string $slug): HexNet
    {
        $hexNet = HexNet::where('slug', $slug)->with('group')->firstOrFail();

        abort_unless($hexNet->isActive() || $request->user()?->can('update', $hexNet), 404);

        return $hexNet;
    }
}
