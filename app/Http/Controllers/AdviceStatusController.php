<?php

namespace App\Http\Controllers;

use App\Models\AdviceStatus;
use Auth;

class AdviceStatusController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        return AdviceStatus::all()->filter(function (AdviceStatus $status) use ($user) {
            return $user->can('view', $status);
        });
    }

    public function show(AdviceStatus $adviceStatus)
    {
        $this->authorize('view', $adviceStatus);

        return $adviceStatus;
    }
}
