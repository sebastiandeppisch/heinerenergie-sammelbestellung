<?php

declare(strict_types=1);

namespace App\HexNet\Services;

use App\HexNet\Data\HexNetStatsData;
use App\HexNet\Enums\HexNetScoringMode;
use App\HexNet\Models\HexNet;
use App\Models\MapPoint;
use App\Models\MapPointCategory;
use App\Models\MapPointCharacteristic;
use App\ValueObjects\Coordinate;
use App\ValueObjects\Polygon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Assigns map points to cells with their exact coordinates. Only ids and counts leave this service.
 */
class HexNetGridService
{
    public const string POINTS_VERSION_KEY = 'hexnet:points:version';

    /** Upper bound for the cells of a play area, it keeps rendering and counting fast. */
    public const int MAX_CELLS = 20000;

    public static function invalidatePoints(): void
    {
        Cache::forever(self::POINTS_VERSION_KEY, Str::random(12));
    }

    /**
     * Rough number of cells of a play area from its bounding box, cheap enough to check before computing the cells.
     */
    public static function estimateCells(HexGrid $grid, Polygon $playArea): int
    {
        $points = array_map($grid->project(...), $playArea->coordinates);
        $xs = array_column($points, 0);
        $ys = array_column($points, 1);

        return $points === [] ? 0 : (int) ceil((max($xs) - min($xs)) * (max($ys) - min($ys)) / ($grid->cellAreaHa() * 10000));
    }

    /**
     * A point counts in a HexaNetz if it lies in the play area and its cell is a play-area cell. Counting, the visible
     * points and the page after the confirmation all use this rule.
     *
     * @param  array<string, int|true>  $cellSet  cell ids as keys
     */
    public static function countsAt(HexGrid $grid, Polygon $playArea, array $cellSet, ?Coordinate $coordinate): bool
    {
        return $coordinate !== null && $grid->contains($playArea, $coordinate) && isset($cellSet[$grid->cellOf($coordinate)]);
    }

    /**
     * @return array<int, string>
     */
    public function cellIds(HexNet $hexNet): array
    {
        $grid = $hexNet->grid();

        if ($grid === null || ! $hexNet->hasPlayArea()) {
            return [];
        }

        /** @var Polygon $playArea */
        $playArea = $hexNet->play_area;

        return Cache::rememberForever(
            'hexnet:cells:'.$this->gridHash($hexNet),
            fn (): array => $grid->cellsInPolygon($playArea),
        );
    }

    /**
     * The cells as GeoJSON polygons with the cell id as property, cached per grid configuration.
     *
     * @return array<string, mixed>
     */
    public function cellsGeoJson(HexNet $hexNet): array
    {
        $grid = $hexNet->grid();

        if ($grid === null) {
            return ['type' => 'FeatureCollection', 'features' => []];
        }

        return Cache::rememberForever('hexnet:geojson:'.$this->gridHash($hexNet), fn (): array => self::toGeoJson($grid, $this->cellIds($hexNet)));
    }

    /**
     * @param  array<int, string>  $cellIds
     * @return array<string, mixed>
     */
    public static function toGeoJson(HexGrid $grid, array $cellIds): array
    {
        return [
            'type' => 'FeatureCollection',
            'features' => array_map(function (string $id) use ($grid): array {
                $ring = array_map(fn (Coordinate $c): array => [round($c->lng, 6), round($c->lat, 6)], $grid->corners(...HexGrid::parseId($id)));
                $ring[] = $ring[0];

                return ['type' => 'Feature', 'properties' => ['id' => $id], 'geometry' => ['type' => 'Polygon', 'coordinates' => [$ring]]];
            }, $cellIds),
        ];
    }

    /**
     * The score per cell of the play area in the unit of the scoring mode, counted regardless of published and consent.
     *
     * @return array<string, int>
     */
    public function counts(HexNet $hexNet): array
    {
        return $this->tally($hexNet)['counts'];
    }

    /**
     * Number of stepping stones per cell, whatever the scoring mode.
     *
     * @return array<string, int>
     */
    public function stoneCounts(HexNet $hexNet): array
    {
        return $this->tally($hexNet)['stones'];
    }

    /**
     * @return array{counts: array<string, int>, stones: array<string, int>}
     */
    private function tally(HexNet $hexNet): array
    {
        $key = sprintf(
            'hexnet:%d:tally:%s:%s:%s:%s',
            $hexNet->id,
            $hexNet->scoring_mode->value,
            Cache::get(self::POINTS_VERSION_KEY, '0'),
            $this->configHash($hexNet),
            $hexNet->scoring_mode === HexNetScoringMode::STONES ? '' : $this->measuresVersion(),
        );

        return Cache::remember($key, now()->addDay(), function () use ($hexNet): array {
            $grid = $hexNet->grid();

            return $grid === null || $hexNet->play_area === null
                ? ['counts' => [], 'stones' => []]
                : $this->tallyIn($hexNet, $grid, $hexNet->play_area, $this->cellIds($hexNet));
        });
    }

    /**
     * Number of points per cell for each measure, keyed by the uuid of the measure. A point with two measures counts
     * for both. Same rules and cache lifetime as counts().
     *
     * @return array<string, array<string, int>>
     */
    public function measureCounts(HexNet $hexNet): array
    {
        $key = sprintf('hexnet:%d:measure-counts:%s:%s:%s', $hexNet->id, Cache::get(self::POINTS_VERSION_KEY, '0'), $this->configHash($hexNet), $this->measuresVersion());

        return Cache::remember($key, now()->addDay(), function () use ($hexNet): array {
            $grid = $hexNet->grid();
            $counts = [];

            if ($grid === null) {
                return [];
            }

            $points = $this->pointsInPlayArea($hexNet, ['id', 'lat', 'lng'], function (Builder $query): void {
                $query->with('characteristics:map_point_characteristics.id,map_point_characteristics.uuid');
            });

            foreach ($points as $point) {
                $cell = $grid->cellOf($point->coordinate);

                foreach ($point->characteristics as $characteristic) {
                    $counts[$characteristic->uuid][$cell] = ($counts[$characteristic->uuid][$cell] ?? 0) + 1;
                }
            }

            return $counts;
        });
    }

    /**
     * Measures are added to and removed from points without saving the point, e.g. by bulk actions, so the pivot
     * table itself is part of the key.
     */
    private function measuresVersion(): string
    {
        $pivot = DB::table('map_point_characteristic_map_point')->selectRaw('count(*) as total, max(updated_at) as latest')->first();

        return md5(($pivot->total ?? 0).'|'.($pivot->latest ?? ''));
    }

    /**
     * The measures the points of the HexaNetz can have. All its categories share them, see HexNetCategoryValidator.
     *
     * @return Collection<int, MapPointCharacteristic>
     */
    public function measures(HexNet $hexNet): Collection
    {
        $categoryId = $hexNet->categories()->value('map_point_categories.id');

        return $categoryId === null ? new Collection : MapPointCharacteristic::selectableFor((int) $categoryId)->with('category')->get()->toBase();
    }

    /**
     * Counts the points of the HexaNetz in another grid or play area, for the preview in the wizard. Not cached.
     *
     * @param  array<int, string>  $cellIds
     * @return array<string, int>
     */
    public function countIn(HexNet $hexNet, HexGrid $grid, Polygon $playArea, array $cellIds): array
    {
        return $this->tallyIn($hexNet, $grid, $playArea, $cellIds)['counts'];
    }

    /**
     * The score per cell in the unit of the scoring mode and the stepping stones per cell. Cells without a score are
     * left out, a stepping stone without measures still counts as stone.
     *
     * @param  array<int, string>  $cellIds
     * @return array{counts: array<string, int>, stones: array<string, int>}
     */
    private function tallyIn(HexNet $hexNet, HexGrid $grid, Polygon $playArea, array $cellIds): array
    {
        $mode = $hexNet->scoring_mode;
        $cells = array_flip($cellIds);
        $stones = [];
        $measures = [];
        $query = $this->pointsQuery($hexNet, $playArea);

        if ($mode !== HexNetScoringMode::STONES) {
            $query->with('characteristics:map_point_characteristics.id');
        }

        foreach ($query->get(['id', 'lat', 'lng']) as $point) {
            if (! self::countsAt($grid, $playArea, $cells, $point->coordinate)) {
                continue;
            }

            $cell = $grid->cellOf($point->coordinate);
            $stones[$cell] = ($stones[$cell] ?? 0) + 1;

            if ($mode !== HexNetScoringMode::STONES) {
                foreach ($point->characteristics as $characteristic) {
                    $measures[$cell][] = (int) $characteristic->id;
                }
            }
        }

        $counts = match ($mode) {
            HexNetScoringMode::STONES => $stones,
            HexNetScoringMode::MEASURES => array_map(count(...), $measures),
            HexNetScoringMode::DISTINCT_MEASURES => array_map(fn (array $ids): int => count(array_unique($ids)), $measures),
        };

        return ['counts' => $counts, 'stones' => $stones];
    }

    /**
     * @param  array<string, int>|null  $counts
     */
    public function stats(HexNet $hexNet, ?array $counts = null): HexNetStatsData
    {
        $counts ??= $this->counts($hexNet);
        $playable = count($this->cellIds($hexNet));
        $levels = [0, 0, 0, 0];

        foreach ($counts as $count) {
            $levels[$hexNet->levelFor($count)]++;
        }

        $levels[0] = max(0, $playable - $levels[1] - $levels[2] - $levels[3]);
        $green = $levels[1] + $levels[2] + $levels[3];

        return new HexNetStatsData(
            percent: $playable === 0 ? 0 : (int) round(100 * $green / $playable),
            playable_cells: $playable,
            levels: $levels,
            total_points: array_sum($this->stoneCounts($hexNet)),
        );
    }

    /**
     * Points of the categories and their subcategories that count in the HexaNetz, see countsAt().
     *
     * @param  array<int, string>  $columns
     * @param  (callable(Builder<MapPoint>): void)|null  $tap
     * @return Collection<int, MapPoint>
     */
    public function pointsInPlayArea(HexNet $hexNet, array $columns = ['*'], ?callable $tap = null): Collection
    {
        $grid = $hexNet->grid();

        if ($grid === null || ! $hexNet->hasPlayArea()) {
            return new Collection;
        }

        /** @var Polygon $playArea */
        $playArea = $hexNet->play_area;
        $query = $this->pointsQuery($hexNet, $playArea);

        if ($tap !== null) {
            $tap($query);
        }

        $cells = array_flip($this->cellIds($hexNet));

        return $query->get($columns)
            ->filter(fn (MapPoint $point): bool => self::countsAt($grid, $playArea, $cells, $point->coordinate))
            ->values()
            ->toBase();
    }

    /**
     * @return array<int, int>
     */
    public function categoryIds(HexNet $hexNet): array
    {
        $ids = $hexNet->categories()->pluck('map_point_categories.id')->map(fn (mixed $id): int => (int) $id)->all();

        return MapPointCategory::tree()->subtreeIds($ids);
    }

    /**
     * @return Builder<MapPoint>
     */
    private function pointsQuery(HexNet $hexNet, Polygon $playArea): Builder
    {
        $lats = array_map(fn (Coordinate $c): float => $c->lat, $playArea->coordinates);
        $lngs = array_map(fn (Coordinate $c): float => $c->lng, $playArea->coordinates);

        return MapPoint::query()
            ->whereIn('category_id', $this->categoryIds($hexNet))
            ->whereBetween('lat', [min($lats), max($lats)])
            ->whereBetween('lng', [min($lngs), max($lngs)]);
    }

    private function gridHash(HexNet $hexNet): string
    {
        return md5(json_encode([$hexNet->grid_origin_lat, $hexNet->grid_origin_lng, $hexNet->hex_radius_m, $hexNet->play_area?->toJson()]) ?: '');
    }

    /**
     * Includes the resolved subtree, so moving a category into or out of the subtree changes the key.
     */
    private function configHash(HexNet $hexNet): string
    {
        $categoryIds = $this->categoryIds($hexNet);
        sort($categoryIds);

        return md5($this->gridHash($hexNet).'|'.$hexNet->updated_at->getTimestamp().'|'.implode(',', $categoryIds));
    }
}
