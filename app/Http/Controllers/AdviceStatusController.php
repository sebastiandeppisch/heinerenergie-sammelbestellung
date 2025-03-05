<?php

namespace App\Http\Controllers;

use App\Data\AdviceStatusNamesData;
use App\Models\AdviceStatus;
use Auth;

class AdviceStatusController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        return AdviceStatus::all()->filter(function (AdviceStatus $status) use ($user) {
            return $user->can('view', $status);
        })->map(fn (AdviceStatus $status) => AdviceStatusNamesData::fromModel($status))->toArray();
    }

    public function show(AdviceStatus $advicestatus)
    {
        $this->authorize('view', $advicestatus);

        return $advicestatus;
    }
}
