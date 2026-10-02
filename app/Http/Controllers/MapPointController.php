<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Context\GroupContextContract;
use App\Data\FormFieldData;
use App\Data\GroupBaseData;
use App\Data\MapPointCategoryData;
use App\Data\MapPointData;
use App\Data\MapPointSpreadsheetMappingData;
use App\Data\SpreadsheetFormatData;
use App\Enums\SpreadsheetFormat;
use App\Http\Requests\DestroyMapPointsRequest;
use App\Http\Requests\UpdateMapPointsCategoryRequest;
use App\Http\Requests\UpdateMapPointsPublishedRequest;
use App\Http\Requests\UpsertMapPointRequest;
use App\Models\FormField;
use App\Models\Group;
use App\Models\MapEmbed;
use App\Models\MapPoint;
use App\Models\MapPointCategory;
use App\Services\CurrentGroupService;
use App\Services\MapPointCharacteristicService;
use App\Services\MapPointFieldService;
use App\Services\MapPointVisibilityService;
use App\ValueObjects\MapPointCategoryTree;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class MapPointController extends Controller
{
    public function map(MapPointVisibilityService $visibility): Response
    {
        $this->authorize('viewAny', MapPoint::class);

        return Inertia::render('MapPoints/Map', [
            'pointsByCategory' => $this->pointData($visibility->visiblePoints(), onlyPublic: false)->groupBy('category_id'),
            'categories' => $this->categoryData($visibility->relevantCategories()->with('group')->get()),
        ]);
    }

    public function index(Request $request, MapPointVisibilityService $visibility, GroupContextContract $groupContext): Response
    {
        $this->authorize('viewAny', MapPoint::class);

        $currentGroup = $groupContext->getCurrentGroup();

        return Inertia::render('MapPoints/Index', [
            'mapPoints' => $this->pointData($visibility->visiblePoints(), onlyPublic: false),
            'categories' => $this->categoryData($categories = $visibility->relevantCategories()->with('group')->get()),
            'usableCategoryIdsByGroup' => $visibility->usableCategoryIdsByGroup($visibility->selectableGroups(), $categories),
            'canImportAndExport' => $request->user()?->can('import', MapPoint::class) === true,
            // System admins may import without a selected group, but imported points need one to belong to.
            'importAndExportNeedGroup' => $currentGroup === null,
            'spreadsheetMappings' => $currentGroup === null ? [] : MapPointSpreadsheetMappingData::forGroup($currentGroup),
            'spreadsheetFormats' => array_map(
                SpreadsheetFormatData::fromEnum(...),
                SpreadsheetFormat::cases(),
            ),
        ]);
    }

    /**
     * Shows the published points of the embed's group and its descendants in the embed's categories.
     * A category includes its sub categories, also those created after the embed. Sub categories of
     * groups outside the embed's map are left out.
     */
    public function publicMap(MapEmbed $mapEmbed): Response
    {
        app(CurrentGroupService::class)->setGroup($mapEmbed->group);

        $tree = MapPointCategory::tree();
        $categories = MapPointCategory::query()
            ->whereIn('id', $tree->subtreeIds($mapEmbed->mapPointCategories()->pluck('map_point_categories.id')->all()))
            ->whereIn('group_id', [...$mapEmbed->group->getHierarchyIds(), ...$mapEmbed->group->getSubtreeIds()])
            ->with('group')
            ->get();

        $mapPoints = MapPoint::query()
            ->visibleFromGroup($mapEmbed->group)
            ->where('published', true)
            ->whereIn('category_id', $categories->pluck('id'));

        return Inertia::render('MapPoints/PublicMap', [
            'pointsByCategory' => $this->pointData($mapPoints, tree: $tree)->groupBy('category_id'),
            'categories' => $this->categoryData($categories, $tree),
            'center' => $mapEmbed->coordinate,
            'zoom' => $mapEmbed->zoom,
            'showTable' => $mapEmbed->show_table,
            'aspectRatioWidth' => $mapEmbed->aspect_ratio_width,
            'aspectRatioHeight' => $mapEmbed->aspect_ratio_height,
        ]);
    }

    public function edit(MapPoint $mappoint, MapPointVisibilityService $visibility, MapPointFieldService $fieldService): Response
    {
        $this->authorize('update', $mappoint);

        return Inertia::render('MapPoints/Upsert', [
            'mapPoint' => MapPointData::fromModel($mappoint->load(['category', 'group']), onlyPublic: false),
            ...$this->formProps($visibility, $fieldService),
        ]);
    }

    public function update(MapPoint $mappoint, UpsertMapPointRequest $request, MapPointFieldService $fieldService): RedirectResponse
    {
        $this->authorize('update', $mappoint);

        DB::transaction(function () use ($mappoint, $request, $fieldService): void {
            $mappoint->update($request->getData());
            $mappoint->characteristics()->sync($request->characteristicIds());
            $fieldService->syncValues($mappoint, $request->fieldValues(), $request->pointFields());
        });

        return redirect()->back()->with('success', 'Der Kartenpunkt wurde aktualisiert');
    }

    public function destroy(MapPoint $mappoint): RedirectResponse
    {
        $this->authorize('delete', $mappoint);

        $name = $mappoint->title;

        $mappoint->delete();

        return redirect()->back()->with('info', 'Der Kartenpunkt '.e($name).' wurde gelöscht');
    }

    /**
     * Deletes the selected points at once, e.g. all points of some categories.
     */
    public function destroyMany(DestroyMapPointsRequest $request): RedirectResponse
    {
        $mapPoints = $request->mapPoints();

        DB::transaction(fn () => $mapPoints->each->delete());

        return redirect()->back()->with('info', $mapPoints->count() === 1 ? 'Ein Kartenpunkt wurde gelöscht' : "{$mapPoints->count()} Kartenpunkte wurden gelöscht");
    }

    /**
     * Moves the selected points into one category at once. Values of fields the new category lacks stay as former values,
     * also those of characteristics the new category does not offer.
     */
    public function updateCategoryOfMany(UpdateMapPointsCategoryRequest $request, MapPointCharacteristicService $characteristicService): RedirectResponse
    {
        $mapPoints = $request->mapPoints();
        $category = $request->category();

        DB::transaction(function () use ($mapPoints, $category, $characteristicService): void {
            $mapPoints->each->update(['category_id' => $category?->id]);
            $characteristicService->detachUnselectable([$category?->id]);
        });

        $count = $mapPoints->count() === 1 ? 'Ein Kartenpunkt' : "{$mapPoints->count()} Kartenpunkte";

        return redirect()->back()->with('success', $category === null ? "{$count} ohne Kategorie" : "{$count} in „{$category->name}“ verschoben");
    }

    public function updatePublishedOfMany(UpdateMapPointsPublishedRequest $request): RedirectResponse
    {
        $mapPoints = $request->mapPoints();
        $published = $request->boolean('published');

        DB::transaction(fn () => $mapPoints->each->update(['published' => $published]));

        $count = $mapPoints->count() === 1 ? 'Ein Kartenpunkt ist' : "{$mapPoints->count()} Kartenpunkte sind";

        return redirect()->back()->with('success', $published ? "{$count} jetzt veröffentlicht" : "{$count} nicht mehr veröffentlicht");
    }

    public function create(MapPointVisibilityService $visibility, MapPointFieldService $fieldService): Response
    {
        $this->authorize('create', MapPoint::class);

        return Inertia::render('MapPoints/Upsert', $this->formProps($visibility, $fieldService));
    }

    public function store(UpsertMapPointRequest $request, MapPointFieldService $fieldService): RedirectResponse
    {
        $this->authorize('create', MapPoint::class);

        $mapPoint = DB::transaction(function () use ($request, $fieldService): MapPoint {
            $mapPoint = MapPoint::create($request->getData());
            $mapPoint->characteristics()->sync($request->characteristicIds());
            $fieldService->syncValues($mapPoint, $request->fieldValues(), $request->pointFields());

            return $mapPoint;
        });

        return redirect()->route('mappoints.edit', $mapPoint)->with('success', 'Der Kartenpunkt wurde erstellt');
    }

    /**
     * The inputs for the category fields are switched in the form when the category changes, so the fields of all categories are sent.
     *
     * @return array{categories: Collection<int, MapPointCategoryData>, groups: Collection<int, GroupBaseData>, usableCategoryIdsByGroup: array<string, array<int, string>>, fieldsByCategory: array<string, array<int, FormFieldData>>, publicFieldIds: array<int, string>}
     */
    private function formProps(MapPointVisibilityService $visibility, MapPointFieldService $fieldService): array
    {
        $categories = $visibility->relevantCategories()->with('group')->get();
        $groups = $visibility->selectableGroups();
        $tree = MapPointCategory::tree();
        $fieldsByCategory = array_map(
            fn (EloquentCollection $fields): array => $fields->map(FormFieldData::fromModel(...))->all(),
            $fieldService->fieldsByCategory($categories, $tree),
        );

        $publicFieldIds = FormField::whereIn('id', MapPointCategory::publicFieldIds())->pluck('uuid')->all();

        return [
            'categories' => $this->categoryData($categories, $tree),
            'groups' => $groups->map(fn (Group $group): GroupBaseData => GroupBaseData::fromModel($group))->toBase(),
            'usableCategoryIdsByGroup' => $visibility->usableCategoryIdsByGroup($groups, $categories),
            'fieldsByCategory' => $fieldsByCategory,
            'publicFieldIds' => $publicFieldIds,
        ];
    }

    /**
     * @param  Builder<MapPoint>  $query
     * @param  bool  $onlyPublic  Pass false only for admins of the points: then internal and former field values are included.
     * @return Collection<int, MapPointData>
     */
    private function pointData(Builder $query, bool $onlyPublic = true, ?MapPointCategoryTree $tree = null): Collection
    {
        $tree ??= MapPointCategory::tree();
        $publicFieldIds = MapPointCategory::publicFieldIds();

        return $query->with(['category', 'group', 'characteristics', 'fields.options', 'fields.formField'])->get()
            ->map(fn (MapPoint $mapPoint): MapPointData => MapPointData::fromModel($mapPoint, $onlyPublic, $tree, $publicFieldIds))
            ->toBase();
    }

    /**
     * @param  EloquentCollection<int, MapPointCategory>  $categories
     * @return Collection<int, MapPointCategoryData>
     */
    private function categoryData(EloquentCollection $categories, ?MapPointCategoryTree $tree = null): Collection
    {
        $tree ??= MapPointCategory::tree();

        return $categories
            ->map(fn (MapPointCategory $category): MapPointCategoryData => MapPointCategoryData::fromModel($category, tree: $tree))
            ->toBase();
    }
}
