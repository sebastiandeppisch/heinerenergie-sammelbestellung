<?php

declare(strict_types=1);

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

        return AdviceStatus::all()->filter(fn (AdviceStatus $status) => $user->can('view', $status))->map(fn (AdviceStatus $status): AdviceStatusNamesData => AdviceStatusNamesData::fromModel($status))->values()->toArray();
    }

    public function show(AdviceStatus $advicestatus): AdviceStatusNamesData
    {
        $this->authorize('view', $advicestatus);

        return AdviceStatusNamesData::fromModel($advicestatus);
    }
}
