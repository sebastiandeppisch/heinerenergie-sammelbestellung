<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\MapPointCategoryData;
use App\Data\MapPointData;
use App\Http\Requests\UpsertMapPointRequest;
use App\Models\MapPoint;
use App\Models\MapPointCategory;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class MapPointController extends Controller
{
    public function map(): Response
    {
        $mapPoints = MapPoint::with('category')->get()->map(fn (MapPoint $mapPoint): MapPointData => MapPointData::fromModel($mapPoint));

        $pointsByCategory = $mapPoints->groupBy('category_id');

        return Inertia::render('MapPoints/Map', [
            'pointsByCategory' => $pointsByCategory,
            'categories' => MapPointCategory::all()->map(fn (MapPointCategory $category): MapPointCategoryData => MapPointCategoryData::fromModel($category)),
        ]);
    }

    public function index(): Response
    {
        $mapPoints = MapPoint::with('category')->get()->map(fn (MapPoint $mapPoint): MapPointData => MapPointData::fromModel($mapPoint));

        return Inertia::render('MapPoints/Index', [
            'mapPoints' => $mapPoints,
            'categories' => MapPointCategory::all()->map(fn (MapPointCategory $category): MapPointCategoryData => MapPointCategoryData::fromModel($category)),
        ]);
    }

    public function publicMap(): Response
    {
        $mapPoints = MapPoint::where('published', true)
            ->with('category')
            ->get()
            ->map(fn (MapPoint $mapPoint): MapPointData => MapPointData::fromModel($mapPoint));

        $pointsByType = $mapPoints->groupBy('userReadablePointableType');

        return Inertia::render('MapPoints/PublicMap', [
            'pointsByType' => $pointsByType,
        ]);
    }

    public function edit(MapPoint $mappoint): Response
    {
        $categories = MapPointCategory::all()->map(fn (MapPointCategory $category): MapPointCategoryData => MapPointCategoryData::fromModel($category));

        return Inertia::render('MapPoints/Upsert', [
            'mapPoint' => MapPointData::fromModel($mappoint->load('category')),
            'categories' => $categories,
        ]);
    }

    public function update(MapPoint $mappoint, UpsertMapPointRequest $request): RedirectResponse
    {
        $mappoint->update($request->getData());

        return redirect()->back()->with('success', 'Der Kartenpunkt wurde aktualisiert');
    }

    public function destroy(MapPoint $mappoint): RedirectResponse
    {

        $name = $mappoint->title;

        $mappoint->delete();

        return redirect()->back()->with('info', 'Der Kartenpunkt '.e($name).' wurde gelöscht');
    }

    public function create(): Response
    {
        $categories = MapPointCategory::all()->map(fn (MapPointCategory $category): MapPointCategoryData => MapPointCategoryData::fromModel($category));

        return Inertia::render('MapPoints/Upsert', [
            'categories' => $categories,
        ]);
    }

    public function store(UpsertMapPointRequest $request): RedirectResponse
    {
        $mapPoint = MapPoint::create($request->getData());

        return redirect()->route('mappoints.edit', $mapPoint)->with('success', 'Der Kartenpunkt wurde erstellt');
    }
}
