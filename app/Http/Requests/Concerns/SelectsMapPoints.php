<?php

declare(strict_types=1);

namespace App\Http\Requests\Concerns;

use App\Models\MapPoint;
use Illuminate\Database\Eloquent\Collection;

/**
 * For actions on many map points at once, selected by their ids.
 */
trait SelectsMapPoints
{
    /** @var Collection<int, MapPoint>|null */
    private ?Collection $selectedMapPoints = null;

    /**
     * @return Collection<int, MapPoint>
     */
    public function mapPoints(): Collection
    {
        $ids = array_filter((array) $this->input('ids', []), is_string(...));

        return $this->selectedMapPoints ??= MapPoint::whereIn('uuid', $ids)->with('group')->get();
    }

    /**
     * Every point needs the right, so a list cannot smuggle in points of other initiatives.
     */
    private function userMayForAllMapPoints(string $ability): bool
    {
        return $this->mapPoints()->every(fn (MapPoint $mapPoint): bool => $this->user()->can($ability, $mapPoint));
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function selectedMapPointRules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'uuid'],
        ];
    }
}
