<?php

namespace App\Services;

use App\Data\StatusDistributionPointData;
use App\Enums\AdviceStatusResult;
use App\Enums\Aggregation;
use App\Events\Advice\StatusChangedEvent;
use App\Models\Advice;
use App\Models\AdviceEvent;
use App\Models\AdviceStatus;
use App\Models\Group;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class AdviceStatusDistributionService
{
    /** @var array<string, AdviceStatusResult> */
    private array $statusNameMap = [];

    /**
     * @return list<StatusDistributionPointData>
     */
    public function getDistribution(?Group $group, Carbon $from, Carbon $to, Aggregation $aggregation): array
    {
        $cutoffDates = $this->generateCutoffDates($from, $to, $aggregation);
        $today = today();

        return array_values(array_map(function (Carbon $cutoffDate) use ($group, $today) {
            if ($cutoffDate->gte($today)) {
                return $this->computeForDate($group, $cutoffDate);
            }

            $cacheKey = 'kpi.status.'.($group->id ?? 'all').'.'.$cutoffDate->format('Y-m-d');

            return Cache::rememberForever($cacheKey, fn (): StatusDistributionPointData => $this->computeForDate($group, $cutoffDate));
        }, $cutoffDates));
    }

    /**
     * @return list<Carbon>
     */
    public function generateCutoffDates(Carbon $from, Carbon $to, Aggregation $aggregation): array
    {
        $dates = [];
        $current = $from->copy()->startOfDay();
        $ceiling = $to->copy()->endOfDay();

        while ($current->lte($to)) {
            $end = match ($aggregation) {
                Aggregation::Day => $current->copy()->endOfDay(),
                Aggregation::Week => $current->copy()->endOfWeek(Carbon::SUNDAY),
                Aggregation::Month => $current->copy()->endOfMonth(),
                Aggregation::Quarter => $current->copy()->endOfQuarter(),
            };

            $dates[] = $end->gt($ceiling) ? $ceiling->copy() : $end;

            $current = match ($aggregation) {
                Aggregation::Day => $current->copy()->addDay()->startOfDay(),
                Aggregation::Week => $current->copy()->addWeek()->startOfWeek(Carbon::MONDAY),
                Aggregation::Month => $current->copy()->addMonthNoOverflow()->startOfMonth(),
                Aggregation::Quarter => $current->copy()->addMonthsNoOverflow(3)->startOfQuarter(),
            };
        }

        return $dates;
    }

    public function computeForDate(?Group $group, Carbon $cutoffDate): StatusDistributionPointData
    {
        $advices = Advice::withTrashed()
            ->where('created_at', '<=', $cutoffDate)
            ->when($group, fn ($q) => $q->where('group_id', $group->id))
            ->with(['status' => fn ($q) => $q->withTrashed()])
            ->get();

        if ($advices->isEmpty()) {
            return $this->emptyDataPoint($cutoffDate);
        }

        $allEvents = AdviceEvent::whereIn('advice_id', $advices->pluck('id'))
            ->whereRaw('event LIKE ?', ['%StatusChangedEvent%'])
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('advice_id');

        $counts = array_fill_keys(
            array_map(fn (AdviceStatusResult $r) => $r->value, AdviceStatusResult::cases()),
            0
        );

        foreach ($advices as $advice) {
            $events = $allEvents->get($advice->id, collect());
            $result = $this->getResultAtDate($advice, $events, $cutoffDate);
            $counts[$result->value]++;
        }

        return new StatusDistributionPointData(
            date: $cutoffDate->format('Y-m-d'),
            statusCounts: $this->mapCountsToLabels($counts),
        );
    }

    /**
     * Determines the AdviceStatusResult an advice was in at the given cutoff date
     * by walking backwards through events that occurred after the cutoff.
     *
     * @param  Collection<int, AdviceEvent>  $events
     */
    public function getResultAtDate(Advice $advice, Collection $events, Carbon $cutoffDate): AdviceStatusResult
    {
        $result = $advice->status->result ?? AdviceStatusResult::New;

        foreach ($events as $adviceEvent) {
            $event = $adviceEvent->event;

            if (! $event instanceof StatusChangedEvent) {
                continue;
            }

            if ($adviceEvent->created_at->gt($cutoffDate)) {
                $result = $this->resolveResult($event->fromStatus);
            } else {
                break;
            }
        }

        return $result;
    }

    private function resolveResult(?string $statusName): AdviceStatusResult
    {
        if ($statusName === null) {
            return AdviceStatusResult::New;
        }

        if ($this->statusNameMap === []) {
            $this->statusNameMap = AdviceStatus::withTrashed()
                ->pluck('result', 'name')
                ->toArray();
        }

        return $this->statusNameMap[$statusName] ?? AdviceStatusResult::New;
    }

    /**
     * @param  array<int, int>  $counts
     * @return array<string, int>
     */
    private function mapCountsToLabels(array $counts): array
    {
        return [
            'Neu' => $counts[AdviceStatusResult::New->value] ?? 0,
            'In Bearbeitung' => $counts[AdviceStatusResult::InProgress->value] ?? 0,
            'Erfolgreich beraten' => $counts[AdviceStatusResult::Completed->value] ?? 0,
            'Nicht erfolgreich' => $counts[AdviceStatusResult::Unsuccessfully->value] ?? 0,
        ];
    }

    private function emptyDataPoint(Carbon $cutoffDate): StatusDistributionPointData
    {
        return new StatusDistributionPointData(
            date: $cutoffDate->format('Y-m-d'),
            statusCounts: [
                'Neu' => 0,
                'In Bearbeitung' => 0,
                'Erfolgreich beraten' => 0,
                'Nicht erfolgreich' => 0,
            ],
        );
    }
}
