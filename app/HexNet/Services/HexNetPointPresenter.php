<?php

declare(strict_types=1);

namespace App\HexNet\Services;

use App\Data\PublicMapPointData;
use App\HexNet\Data\HexNetPointData;
use App\HexNet\Models\HexNet;
use App\Models\MapPoint;
use App\Models\MapPointCategory;
use App\Services\PublicMapPointPresenter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * The visible points of a HexaNetz as the public sees them, moved by the jitter.
 */
class HexNetPointPresenter
{
    public function __construct(
        private readonly PublicMapPointPresenter $presenter,
        private readonly HexNetGridService $gridService,
        private readonly HexNetJitter $jitter,
    ) {}

    /**
     * @return Collection<int, HexNetPointData>
     */
    public function presentAll(HexNet $hexNet): Collection
    {
        $grid = $hexNet->grid();

        if ($grid === null) {
            return new Collection;
        }

        $tree = MapPointCategory::tree();
        $publicFieldIds = MapPointCategory::publicFieldIds();

        return $this->gridService
            ->pointsInPlayArea($hexNet, ['*'], /** @param Builder<MapPoint> $query */ function (Builder $query): void {
                $this->presenter->scopeVisible($query)
                    ->with(['category', 'group', 'characteristics.category', 'fields.options', 'fields.formField']);
            })
            ->map(function (MapPoint $mapPoint) use ($hexNet, $tree, $publicFieldIds): HexNetPointData {
                $data = PublicMapPointData::fromModel($mapPoint, $tree, $publicFieldIds);
                $data->coordinate = $this->jitter->apply($mapPoint->coordinate, $mapPoint->uuid, $hexNet->jitter_m);

                return new HexNetPointData(
                    point: $data,
                    since: (int) $mapPoint->created_at->format('Y'),
                );
            })
            ->values();
    }
}
