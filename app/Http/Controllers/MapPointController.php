<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Context\GroupContextContract;
use App\Data\GroupBaseData;
use App\Data\MapPointCategoryData;
use App\Data\MapPointData;
use App\Data\MapPointSpreadsheetMappingData;
use App\Data\SpreadsheetFormatData;
use App\Enums\SpreadsheetFormat;
use App\Http\Requests\UpsertMapPointRequest;
use App\Models\Group;
use App\Models\MapEmbed;
use App\Models\MapPoint;
use App\Models\MapPointCategory;
use App\Services\CurrentGroupService;
use App\Services\MapPointVisibilityService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class MapPointController extends Controller
{
    public function map(MapPointVisibilityService $visibility): Response
    {
        $this->authorize('viewAny', MapPoint::class);

        return Inertia::render('MapPoints/Map', [
            'pointsByCategory' => $this->pointData($visibility->visiblePoints())->groupBy('category_id'),
            'categories' => $this->categoryData($visibility->relevantCategories()->with('group')->get()),
        ]);
    }

    public function index(Request $request, MapPointVisibilityService $visibility, GroupContextContract $groupContext): Response
    {
        $this->authorize('viewAny', MapPoint::class);

        $currentGroup = $groupContext->getCurrentGroup();

        return Inertia::render('MapPoints/Index', [
            'mapPoints' => $this->pointData($visibility->visiblePoints()),
            'categories' => $this->categoryData($visibility->relevantCategories()->with('group')->get()),
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
     */
    public function publicMap(MapEmbed $mapEmbed): Response
    {
        app(CurrentGroupService::class)->setGroup($mapEmbed->group);

        $categories = $mapEmbed->mapPointCategories()->with('group')->get();

        $mapPoints = MapPoint::query()
            ->visibleFromGroup($mapEmbed->group)
            ->where('published', true)
            ->whereIn('category_id', $categories->pluck('id'));

        return Inertia::render('MapPoints/PublicMap', [
            'pointsByCategory' => $this->pointData($mapPoints)->groupBy('category_id'),
            'categories' => $this->categoryData($categories),
            'center' => $mapEmbed->coordinate,
            'zoom' => $mapEmbed->zoom,
            'showTable' => $mapEmbed->show_table,
            'aspectRatioWidth' => $mapEmbed->aspect_ratio_width,
            'aspectRatioHeight' => $mapEmbed->aspect_ratio_height,
        ]);
    }

    public function edit(MapPoint $mappoint, MapPointVisibilityService $visibility): Response
    {
        $this->authorize('update', $mappoint);

        return Inertia::render('MapPoints/Upsert', [
            'mapPoint' => MapPointData::fromModel($mappoint->load(['category', 'group'])),
            ...$this->formProps($visibility),
        ]);
    }

    public function update(MapPoint $mappoint, UpsertMapPointRequest $request): RedirectResponse
    {
        $this->authorize('update', $mappoint);

        $mappoint->update($request->getData());

        return redirect()->back()->with('success', 'Der Kartenpunkt wurde aktualisiert');
    }

    public function destroy(MapPoint $mappoint): RedirectResponse
    {
        $this->authorize('delete', $mappoint);

        $name = $mappoint->title;

        $mappoint->delete();

        return redirect()->back()->with('info', 'Der Kartenpunkt '.e($name).' wurde gelöscht');
    }

    public function create(MapPointVisibilityService $visibility): Response
    {
        $this->authorize('create', MapPoint::class);

        return Inertia::render('MapPoints/Upsert', $this->formProps($visibility));
    }

    public function store(UpsertMapPointRequest $request): RedirectResponse
    {
        $this->authorize('create', MapPoint::class);

        $mapPoint = MapPoint::create($request->getData());

        return redirect()->route('mappoints.edit', $mapPoint)->with('success', 'Der Kartenpunkt wurde erstellt');
    }

    /**
     * @return array{categories: Collection<int, MapPointCategoryData>, groups: Collection<int, GroupBaseData>, usableCategoryIdsByGroup: array<string, array<int, string>>}
     */
    private function formProps(MapPointVisibilityService $visibility): array
    {
        $categories = $visibility->relevantCategories()->with('group')->get();
        $groups = $visibility->selectableGroups();

        return [
            'categories' => $this->categoryData($categories),
            'groups' => $groups->map(fn (Group $group): GroupBaseData => GroupBaseData::fromModel($group))->toBase(),
            'usableCategoryIdsByGroup' => $visibility->usableCategoryIdsByGroup($groups, $categories),
        ];
    }

    /**
     * @param  Builder<MapPoint>  $query
     * @return Collection<int, MapPointData>
     */
    private function pointData(Builder $query): Collection
    {
        return $query->with(['category', 'group'])->get()
            ->map(fn (MapPoint $mapPoint): MapPointData => MapPointData::fromModel($mapPoint))
            ->toBase();
    }

    /**
     * @param  EloquentCollection<int, MapPointCategory>  $categories
     * @return Collection<int, MapPointCategoryData>
     */
    private function categoryData(EloquentCollection $categories): Collection
    {
        return $categories
            ->map(fn (MapPointCategory $category): MapPointCategoryData => MapPointCategoryData::fromModel($category))
            ->toBase();
    }
}
