<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MapPointCategory;
use App\ValueObjects\MapPointCategoryTree;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Keeps the characteristics of points consistent with their categories. A point can only have characteristics of
 * its category or of an ancestor, so changing its category or moving a category in the tree can remove some.
 */
class MapPointCharacteristicService
{
    /**
     * Removes the characteristics the points of the given categories can no longer have. Their values stay on the
     * points as former values and become active again when the characteristic is added back.
     *
     * @param  array<int, int|null>  $categoryIds  null stands for points without category
     */
    public function detachUnselectable(array $categoryIds, ?MapPointCategoryTree $tree = null): void
    {
        $tree ??= MapPointCategory::tree();
        $ids = array_values(array_filter($categoryIds, fn (?int $id): bool => $id !== null));

        $assignments = DB::table('map_point_characteristic_map_point')
            ->join('map_points', 'map_points.id', '=', 'map_point_characteristic_map_point.map_point_id')
            ->join('map_point_characteristics', 'map_point_characteristics.id', '=', 'map_point_characteristic_map_point.map_point_characteristic_id')
            ->where(function (Builder $query) use ($ids, $categoryIds): void {
                $query->whereIn('map_points.category_id', $ids);

                if (in_array(null, $categoryIds, true)) {
                    $query->orWhereNull('map_points.category_id');
                }
            })
            ->get(['map_point_characteristic_map_point.id', 'map_points.category_id', 'map_point_characteristics.map_point_category_id']);

        $unselectableIds = $assignments
            ->reject(fn (object $assignment): bool => $assignment->category_id !== null && in_array(
                (int) $assignment->map_point_category_id,
                [(int) $assignment->category_id, ...$tree->ancestorIds((int) $assignment->category_id)],
                true,
            ))
            ->pluck('id');

        DB::table('map_point_characteristic_map_point')->whereIn('id', $unselectableIds)->delete();
    }
}
