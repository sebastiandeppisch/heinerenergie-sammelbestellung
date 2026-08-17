<?php

namespace App\Http\Controllers;

use App\Data\MapEmbedData;
use App\Data\MapPointCategoryData;
use App\Data\MapPointData;
use App\Http\Requests\UpsertMapEmbedRequest;
use App\Models\MapEmbed;
use App\Models\MapPoint;
use App\Models\MapPointCategory;
use Illuminate\Support\Collection;
use Inertia\Inertia;

class MapEmbedController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', MapEmbed::class);

        $mapEmbeds = MapEmbed::with('mapPointCategories')->latest()->get()
            ->map(fn (MapEmbed $mapEmbed): MapEmbedData => MapEmbedData::fromModel($mapEmbed));

        return Inertia::render('MapPoints/Embeds/Index', [
            'mapEmbeds' => $mapEmbeds,
        ]);
    }

    public function create()
    {
        $this->authorize('create', MapEmbed::class);

        return Inertia::render('MapPoints/Embeds/Upsert', [
            'categories' => $this->allCategories(),
            'pointsByCategory' => $this->publishedPointsByCategory(),
        ]);
    }

    public function store(UpsertMapEmbedRequest $request)
    {
        $this->authorize('create', MapEmbed::class);

        $mapEmbed = MapEmbed::create($request->getData());
        $mapEmbed->mapPointCategories()->sync($this->resolveCategoryIds($request));

        return redirect()->route('map-embeds.edit', $mapEmbed)->with('success', 'Die Einbettung wurde erstellt');
    }

    public function edit(MapEmbed $mapEmbed)
    {
        $this->authorize('update', $mapEmbed);

        return Inertia::render('MapPoints/Embeds/Upsert', [
            'mapEmbed' => MapEmbedData::fromModel($mapEmbed->load('mapPointCategories')),
            'categories' => $this->allCategories(),
            'pointsByCategory' => $this->publishedPointsByCategory(),
        ]);
    }

    public function update(UpsertMapEmbedRequest $request, MapEmbed $mapEmbed)
    {
        $this->authorize('update', $mapEmbed);

        $mapEmbed->update($request->getData());
        $mapEmbed->mapPointCategories()->sync($this->resolveCategoryIds($request));

        return redirect()->back()->with('success', 'Die Einbettung wurde aktualisiert');
    }

    public function destroy(MapEmbed $mapEmbed)
    {
        $this->authorize('delete', $mapEmbed);

        $name = $mapEmbed->name ?? $mapEmbed->uuid;

        $mapEmbed->delete();

        return redirect()->route('map-embeds.index')->with('info', 'Die Einbettung '.e($name).' wurde gelöscht');
    }

    /**
     * @return Collection<int, MapPointCategoryData>
     */
    private function allCategories()
    {
        return MapPointCategory::all()->map(fn (MapPointCategory $category): MapPointCategoryData => MapPointCategoryData::fromModel($category));
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
