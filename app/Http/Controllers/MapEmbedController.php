<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\GroupBaseData;
use App\Data\MapEmbedData;
use App\Data\MapPointCategoryData;
use App\Data\MapPointData;
use App\Http\Requests\UpsertMapEmbedRequest;
use App\Models\Group;
use App\Models\MapEmbed;
use App\Models\MapPoint;
use App\Models\MapPointCategory;
use App\Services\MapPointVisibilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class MapEmbedController extends Controller
{
    /**
     * Lists the embeds of the current group and its descendants.
     */
    public function index(MapPointVisibilityService $visibility): Response
    {
        $this->authorize('viewAny', MapEmbed::class);

        $groupIds = $visibility->selectableGroups()->modelKeys();

        $mapEmbeds = MapEmbed::with('mapPointCategories.group', 'group')
            ->whereIn('group_id', $groupIds)
            ->latest()
            ->get()
            ->map(fn (MapEmbed $mapEmbed): MapEmbedData => MapEmbedData::fromModel($mapEmbed));

        return Inertia::render('MapPoints/Embeds/Index', [
            'mapEmbeds' => $mapEmbeds,
        ]);
    }

    public function create(MapPointVisibilityService $visibility): Response
    {
        $this->authorize('create', MapEmbed::class);

        return Inertia::render('MapPoints/Embeds/Upsert', $this->formProps($visibility));
    }

    public function store(UpsertMapEmbedRequest $request): RedirectResponse
    {
        $this->authorize('create', MapEmbed::class);

        $mapEmbed = MapEmbed::create($request->getData());
        $mapEmbed->mapPointCategories()->sync($this->resolveCategoryIds($request));

        return redirect()->route('map-embeds.edit', $mapEmbed)->with('success', 'Die Einbettung wurde erstellt');
    }

    public function edit(MapEmbed $mapEmbed, MapPointVisibilityService $visibility): Response
    {
        $this->authorize('update', $mapEmbed);

        return Inertia::render('MapPoints/Embeds/Upsert', [
            'mapEmbed' => MapEmbedData::fromModel($mapEmbed->load('mapPointCategories.group', 'group')),
            ...$this->formProps($visibility),
        ]);
    }

    public function update(UpsertMapEmbedRequest $request, MapEmbed $mapEmbed): RedirectResponse
    {
        $this->authorize('update', $mapEmbed);

        $mapEmbed->update($request->getData());
        $mapEmbed->mapPointCategories()->sync($this->resolveCategoryIds($request));

        return redirect()->back()->with('success', 'Die Einbettung wurde aktualisiert');
    }

    public function destroy(MapEmbed $mapEmbed): RedirectResponse
    {
        $this->authorize('delete', $mapEmbed);

        $name = $mapEmbed->name ?? $mapEmbed->uuid;

        $mapEmbed->delete();

        return redirect()->route('map-embeds.index')->with('info', 'Die Einbettung '.e($name).' wurde gelöscht');
    }

    /**
     * @return array{categories: Collection<int, MapPointCategoryData>, pointsByCategory: Collection<string, Collection<int, MapPointData>>, groups: Collection<int, GroupBaseData>, mapCategoryIdsByGroup: array<string, array<int, string>>}
     */
    private function formProps(MapPointVisibilityService $visibility): array
    {
        $categories = $visibility->relevantCategories()->with('group')->get();
        $groups = $visibility->selectableGroups();

        return [
            'categories' => $categories
                ->map(fn (MapPointCategory $category): MapPointCategoryData => MapPointCategoryData::fromModel($category))
                ->toBase(),
            'pointsByCategory' => $this->publishedPointsByCategory($visibility),
            'groups' => $groups->map(fn (Group $group): GroupBaseData => GroupBaseData::fromModel($group))->toBase(),
            'mapCategoryIdsByGroup' => $visibility->mapCategoryIdsByGroup($groups, $categories),
        ];
    }

    /**
     * @return array<int, int>
     */
    private function resolveCategoryIds(UpsertMapEmbedRequest $request): array
    {
        return MapPointCategory::whereIn('uuid', $request->validated('category_ids'))->pluck('id')->all();
    }

    /**
     * @return Collection<string, Collection<int, MapPointData>>
     */
    private function publishedPointsByCategory(MapPointVisibilityService $visibility): Collection
    {
        return $visibility->visiblePoints()
            ->where('published', true)
            ->with(['category', 'group'])
            ->get()
            ->map(fn (MapPoint $mapPoint): MapPointData => MapPointData::fromModel($mapPoint))
            ->groupBy('category_id');
    }
}
