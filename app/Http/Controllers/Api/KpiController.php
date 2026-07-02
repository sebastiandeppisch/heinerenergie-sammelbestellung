<?php

namespace App\Http\Controllers\Api;

use App\Data\StatusDistributionPointData;
use App\Data\StatusNameCountData;
use App\Data\YearlyAdviceCountData;
use App\Enums\Aggregation;
use App\Http\Controllers\Controller;
use App\Services\AdviceMonthlyCountService;
use App\Services\AdviceStatusDistributionService;
use App\Services\CurrentGroupService;
use App\Services\CurrentStatusDistributionService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rules\Enum;

class KpiController extends Controller
{
    /**
     * @return list<StatusDistributionPointData>
     */
    public function statusDistribution(
        Request $request,
        AdviceStatusDistributionService $service,
        CurrentGroupService $currentGroupService,
    ): array {
        $validated = $request->validate([
            'from' => 'required|date',
            'to' => 'nullable|date|after_or_equal:from',
            'aggregation' => ['required', new Enum(Aggregation::class)],
        ]);

        return $service->getDistribution(
            group: $currentGroupService->getGroup(),
            from: Carbon::parse($validated['from']),
            to: Carbon::parse($validated['to'] ?? now()),
            aggregation: Aggregation::from($validated['aggregation']),
        );
    }

    /**
     * @return array{series: list<YearlyAdviceCountData>, monthLabels: list<string>}
     */
    public function monthlyCount(
        Request $request,
        AdviceMonthlyCountService $service,
        CurrentGroupService $currentGroupService,
    ): array {
        $validated = $request->validate([
            'years' => 'required|integer|min:1|max:5',
        ]);

        return [
            'series' => $service->getSeries(
                group: $currentGroupService->getGroup(),
                years: (int) $validated['years'],
            ),
            'monthLabels' => $service->getMonthLabels(),
        ];
    }

    /**
     * @return list<StatusNameCountData>
     */
    public function currentStatusDistribution(
        CurrentStatusDistributionService $service,
        CurrentGroupService $currentGroupService,
    ): array {
        return $service->getDistribution(
            group: $currentGroupService->getGroup(),
        );
    }
}
