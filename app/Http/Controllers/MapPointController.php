<?php

namespace App\Http\Controllers;

use App\Data\MapPointData;
use App\Http\Requests\UpsertMapPointRequest;
use App\Models\FormSubmission;
use App\Models\MapPoint;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;

class MapPointController extends Controller
{
    public function map(){
        $mapPoints = MapPoint::all()->map(fn (MapPoint $mapPoint): MapPointData => MapPointData::fromModel($mapPoint));

        $pointsByType = $mapPoints->groupBy('userReadablePointableType');

        return Inertia::render('MapPoints/Map', [
            'pointsByType' => $pointsByType
        ]);
    }

    public function index()
    {
        $mapPoints = MapPoint::all()->map(fn (MapPoint $mapPoint): MapPointData => MapPointData::fromModel($mapPoint));

        return Inertia::render('MapPoints/Index', [
            'mapPoints' => $mapPoints
        ]);
    }

    public function publicMap()
    {
        $mapPoints = MapPoint::where('published', true)
            ->get()
            ->map(fn (MapPoint $mapPoint): MapPointData => MapPointData::fromModel($mapPoint));

        $pointsByType = $mapPoints->groupBy('userReadablePointableType');

        return Inertia::render('MapPoints/PublicMap', [
            'pointsByType' => $pointsByType
        ]);
    }

    public function edit(MapPoint $mappoint)
    {
        return Inertia::render('MapPoints/Edit', [
            'mapPoint' => MapPointData::fromModel($mappoint)
        ]);
    }

    public function update(MapPoint $mappoint, UpsertMapPointRequest $request){
        $mappoint->update($request->validated());
        return redirect()->back()->with('success', 'Der Kartenpunkt wurde aktualisiert');
    }

    public function destroy(MapPoint $mappoint){

        $name = $mappoint->title;

        $mappoint->delete();
        return redirect()->back()->with('info', 'Der Kartenpunkt '.e($name).' wurde gelöscht');
    }


}
