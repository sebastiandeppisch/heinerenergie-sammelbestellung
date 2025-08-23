<?php

namespace App\Http\Controllers;

use App\Data\MapPointCategoryData;
use App\Data\MapPointData;
use App\Http\Requests\UpsertMapPointRequest;
use App\Models\MapPoint;
use App\Models\MapPointCategory;
use Inertia\Inertia;

class MapPointController extends Controller
{
    public function map()
    {
        $mapPoints = MapPoint::with('category')->get()->map(fn (MapPoint $mapPoint): MapPointData => MapPointData::fromModel($mapPoint));

        $pointsByCategory = $mapPoints->groupBy('category_id');

        return Inertia::render('MapPoints/Map', [
            'pointsByCategory' => $pointsByCategory,
            'categories' => MapPointCategory::all()->map(fn ($category) => MapPointCategoryData::fromModel($category)),
        ]);
    }

    public function index()
    {
        $mapPoints = MapPoint::with('category')->get()->map(fn (MapPoint $mapPoint): MapPointData => MapPointData::fromModel($mapPoint));

        return Inertia::render('MapPoints/Index', [
            'mapPoints' => $mapPoints,
            'categories' => MapPointCategory::all()->map(fn ($category) => MapPointCategoryData::fromModel($category)),
        ]);
    }

    public function publicMap()
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

    public function edit(MapPoint $mappoint)
    {
        $categories = MapPointCategory::all()->map(fn (MapPointCategory $category): MapPointCategoryData => MapPointCategoryData::fromModel($category));

        return Inertia::render('MapPoints/Upsert', [
            'mapPoint' => MapPointData::fromModel($mappoint->load('category')),
            'categories' => $categories,
        ]);
    }

    public function update(MapPoint $mappoint, UpsertMapPointRequest $request)
    {
        $mappoint->update($request->getData());

        return redirect()->back()->with('success', 'Der Kartenpunkt wurde aktualisiert');
    }

    public function destroy(MapPoint $mappoint)
    {

        $name = $mappoint->title;

        $mappoint->delete();

        return redirect()->back()->with('info', 'Der Kartenpunkt '.e($name).' wurde gelöscht');
    }

    public function create()
    {
        $categories = MapPointCategory::all()->map(fn (MapPointCategory $category): MapPointCategoryData => MapPointCategoryData::fromModel($category));

        return Inertia::render('MapPoints/Upsert', [
            'categories' => $categories,
        ]);
    }

    public function store(UpsertMapPointRequest $request)
    {
        $mapPoint = MapPoint::create($request->getData());

        return redirect()->route('mappoints.edit', $mapPoint)->with('success', 'Der Kartenpunkt wurde erstellt');
    }
}
