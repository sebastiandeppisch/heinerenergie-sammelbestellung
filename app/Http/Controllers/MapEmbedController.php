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
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class MapEmbedController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', MapEmbed::class);

        $mapEmbeds = MapEmbed::with('mapPointCategories', 'group')->latest()->get()
            ->map(fn (MapEmbed $mapEmbed): MapEmbedData => MapEmbedData::fromModel($mapEmbed));

        return Inertia::render('MapPoints/Embeds/Index', [
            'mapEmbeds' => $mapEmbeds,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', MapEmbed::class);

        return Inertia::render('MapPoints/Embeds/Upsert', [
            'categories' => $this->allCategories(),
            'pointsByCategory' => $this->publishedPointsByCategory(),
            'groups' => $this->selectableGroups(),
        ]);
    }

    public function store(UpsertMapEmbedRequest $request): RedirectResponse
    {
        $this->authorize('create', MapEmbed::class);

        $mapEmbed = MapEmbed::create($request->getData());
        $mapEmbed->mapPointCategories()->sync($this->resolveCategoryIds($request));

        return redirect()->route('map-embeds.edit', $mapEmbed)->with('success', 'Die Einbettung wurde erstellt');
    }

    public function edit(MapEmbed $mapEmbed): Response
    {
        $this->authorize('update', $mapEmbed);

        return Inertia::render('MapPoints/Embeds/Upsert', [
            'mapEmbed' => MapEmbedData::fromModel($mapEmbed->load('mapPointCategories', 'group')),
            'categories' => $this->allCategories(),
            'pointsByCategory' => $this->publishedPointsByCategory(),
            'groups' => $this->selectableGroups(),
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
     * @return Collection<int, MapPointCategoryData>
     */
    private function allCategories(): Collection
    {
        return MapPointCategory::all()->map(fn (MapPointCategory $category): MapPointCategoryData => MapPointCategoryData::fromModel($category));
    }

    /**
     * The initiatives an embed can be assigned to. Its primary color themes the public map.
     *
     * @return Collection<int, GroupBaseData>
     */
    private function selectableGroups(): Collection
    {
        return Group::all()->map(fn (Group $group): GroupBaseData => GroupBaseData::fromModel($group));
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
    private function publishedPointsByCategory(): Collection
    {
        return MapPoint::where('published', true)
            ->with('category')
            ->get()
            ->map(fn (MapPoint $mapPoint): MapPointData => MapPointData::fromModel($mapPoint))
            ->groupBy('category_id');
    }
}
