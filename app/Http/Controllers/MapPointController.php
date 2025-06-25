<?php

namespace App\Http\Controllers;

use App\Data\MapPointData;
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


}
