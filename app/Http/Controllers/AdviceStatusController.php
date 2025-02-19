<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAdviceStatusRequest;
use App\Http\Requests\UpdateAdviceStatusRequest;
use App\Models\AdviceStatus;

class AdviceStatusController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(AdviceStatus::class, 'advicestatus');
    }

    public function index()
    {
        return AdviceStatus::all();
    }

    public function store(StoreAdviceStatusRequest $request)
    {
        $advicestatus = new AdviceStatus;
        $advicestatus->fill($request->validated());
        $advicestatus->save();

        return $advicestatus;
    }

    public function show(AdviceStatus $advicestatus)
    {
        return $advicestatus;
    }

    public function update(UpdateAdviceStatusRequest $request, AdviceStatus $advicestatus)
    {
        $advicestatus->fill($request->validated());
        $advicestatus->save();

        return $advicestatus;
    }

    public function destroy(AdviceStatus $advicestatus)
    {
        $advicestatus->delete();

        return response()->noContent();
    }
}
