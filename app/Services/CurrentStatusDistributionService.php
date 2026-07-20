<?php

namespace App\Services;

use App\Data\StatusNameCountData;
use App\Enums\AdviceStatusResult;
use App\Models\Group;
use Illuminate\Support\Facades\DB;

class CurrentStatusDistributionService
{
    /**
     * Returns the count of current (non-deleted) advices grouped by their status name.
     * Advices without a status are bucketed as "Ohne Status" with result New.
     *
     * @return list<StatusNameCountData>
     */
    public function getDistribution(?Group $group): array
    {
        $rows = DB::table('advices')
            ->leftJoin('advice_status', 'advices.advice_status_id', '=', 'advice_status.id')
            ->whereNull('advices.deleted_at')
            ->when($group, fn ($q) => $q->where('advices.group_id', $group->id))
            ->selectRaw('advice_status.name as status_name, advice_status.result as status_result, COUNT(*) as count')
            ->groupBy('advice_status.id', 'advice_status.name', 'advice_status.result')
            ->orderByDesc('count')
            ->get();

        return $rows->map(function ($row) {
            $result = $row->status_result !== null
                ? AdviceStatusResult::from((int) $row->status_result)
                : AdviceStatusResult::New;

            return new StatusNameCountData(
                name: $row->status_name ?? 'Ohne Status',
                result: $result,
                count: (int) $row->count,
            );
        })->values()->all();
    }
}
