<?php

namespace App\Services;

use App\Data\YearlyAdviceCountData;
use App\Models\Advice;
use App\Models\Group;

class AdviceMonthlyCountService
{
    /**
     * Returns N rolling 12-month series, each ending at the current month.
     * Series are ordered most-recent-first (index 0 = current year).
     *
     * @return list<YearlyAdviceCountData>
     */
    public function getSeries(?Group $group, int $years): array
    {
        $currentMonthStart = now()->startOfMonth();
        $from = $currentMonthStart->copy()->subMonths($years * 12 - 1);

        $monthlyCounts = Advice::selectRaw('SUBSTR(created_at, 1, 7) as ym, COUNT(*) as count')
            ->where('created_at', '>=', $from)
            ->where('created_at', '<=', now()->endOfMonth())
            ->when($group, fn ($q) => $q->where('group_id', $group->id))
            ->groupBy('ym')
            ->pluck('count', 'ym')
            ->toArray();

        $series = [];
        for ($y = 0; $y < $years; $y++) {
            $seriesEnd = $currentMonthStart->copy()->subMonths($y * 12);
            $seriesStart = $seriesEnd->copy()->subMonths(11);

            $counts = [];
            $cursor = $seriesStart->copy();
            while ($cursor->lte($seriesEnd)) {
                $counts[] = (int) ($monthlyCounts[$cursor->format('Y-m')] ?? 0);
                $cursor->addMonth();
            }

            $series[] = new YearlyAdviceCountData(
                label: $seriesStart->year === $seriesEnd->year
                    ? (string) $seriesEnd->year
                    : $seriesStart->year.'/'.$seriesEnd->format('y'),
                counts: $counts,
            );
        }

        return $series;
    }

    /**
     * Returns the 12 month labels for the current rolling window (e.g. ["Jul", "Aug", ..., "Jun"]).
     *
     * @return list<string>
     */
    public function getMonthLabels(): array
    {
        $labels = [];
        $cursor = now()->subMonths(11)->startOfMonth();
        for ($i = 0; $i < 12; $i++) {
            $labels[] = $cursor->locale('de')->isoFormat('MMM');
            $cursor->addMonth();
        }

        return $labels;
    }
}
