<?php

declare(strict_types=1);

namespace App\HexNet\Services;

use App\HexNet\Data\HexNetStatsData;
use App\HexNet\Models\HexNet;
use App\Models\MapPoint;
use App\Models\MapPointCategory;
use App\ValueObjects\Coordinate;
use App\ValueObjects\Polygon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
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
     * Number of points per cell of the play area, counted regardless of published and consent.
     *
     * @return array<string, int>
     */
    public function counts(HexNet $hexNet): array
    {
        $key = sprintf('hexnet:%d:counts:%s:%s', $hexNet->id, Cache::get(self::POINTS_VERSION_KEY, '0'), $this->configHash($hexNet));

        return Cache::remember($key, now()->addDay(), function () use ($hexNet): array {
            $grid = $hexNet->grid();

            return $grid === null || $hexNet->play_area === null ? [] : $this->countIn($hexNet, $grid, $hexNet->play_area, $this->cellIds($hexNet));
        });
    }

    /**
     * Counts the points of the HexaNetz in another grid or play area, for the preview in the wizard. Not cached.
     *
     * @param  array<int, string>  $cellIds
     * @return array<string, int>
     */
    public function countIn(HexNet $hexNet, HexGrid $grid, Polygon $playArea, array $cellIds): array
    {
        $cells = array_flip($cellIds);
        $counts = [];

        foreach ($this->pointsQuery($hexNet, $playArea)->get(['id', 'lat', 'lng']) as $point) {
            if (self::countsAt($grid, $playArea, $cells, $point->coordinate)) {
                $cell = $grid->cellOf($point->coordinate);
                $counts[$cell] = ($counts[$cell] ?? 0) + 1;
            }
        }

        return $counts;
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
            total_points: array_sum($counts),
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
