<?php

namespace App\Http\Controllers\Api;

use App\Data\AdviceStatusNamesData;
use App\Http\Controllers\Controller;
use App\Models\AdviceStatus;
use Illuminate\Support\Facades\Auth;

class AdviceStatusController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        return AdviceStatus::all()->filter(fn(AdviceStatus $status) => $user->can('view', $status))->map(fn (AdviceStatus $status) => AdviceStatusNamesData::fromModel($status))->toArray();
    }

    public function show(AdviceStatus $advicestatus)
    {
        $this->authorize('view', $advicestatus);

        return $advicestatus;
    }
}
