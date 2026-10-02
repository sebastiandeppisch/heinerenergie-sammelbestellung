<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\FormFieldData;
use App\Data\MapPointCharacteristicData;
use App\Models\MapPointCategory;
use App\Models\MapPointCharacteristic;
use App\ValueObjects\MapPointCategoryTree;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * A point can only have characteristics of its category or of an ancestor. This keeps the characteristics of points
 * consistent with their categories, and tells forms which characteristics each category offers.
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

    /**
     * The characteristics the categories offer, which ones points of each category can have, and their fields.
     *
     * @param  EloquentCollection<int, MapPointCategory>  $categories
     * @return array{characteristics: array<int, MapPointCharacteristicData>, characteristicIdsByCategory: array<string, array<int, string>>, fieldsByCharacteristic: array<string, array<int, FormFieldData>>}
     */
    public function characteristicProps(EloquentCollection $categories, MapPointCategoryTree $tree): array
    {
        $characteristics = MapPointCharacteristic::query()
            ->whereIn('map_point_category_id', $categories->modelKeys())
            ->with(['category', 'formDefinition.fields.options'])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return [
            'characteristics' => $characteristics->map(MapPointCharacteristicData::fromModel(...))->all(),
            'characteristicIdsByCategory' => $categories->mapWithKeys(fn (MapPointCategory $category): array => [
                $category->uuid => $characteristics
                    ->filter(fn (MapPointCharacteristic $characteristic): bool => $characteristic->isSelectableFor($category->id, $tree))
                    ->pluck('uuid')
                    ->values()
                    ->all(),
            ])->all(),
            'fieldsByCharacteristic' => $characteristics->mapWithKeys(fn (MapPointCharacteristic $characteristic): array => [
                $characteristic->uuid => $characteristic->formDefinition === null ? [] : $characteristic->formDefinition->fields->map(FormFieldData::fromModel(...))->all(),
            ])->all(),
        ];
    }
}
