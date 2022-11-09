<?php

namespace App\Http\Controllers;

use App\Models\AdviceStatus;
use Illuminate\Http\Request;
use App\Http\Requests\StoreAdviceStatusRequest;
use App\Http\Requests\UpdateAdviceStatusRequest;

class AdviceStatusController extends Controller
{
    public function index()
    {
        return AdviceStatus::all();
    }

    public function store(StoreAdviceStatusRequest $request){
        $advicestatus = new AdviceStatus();
        $advicestatus->fill($request->validated());
        $advicestatus->save();
        return $advicestatus;
    }

    public function show(AdviceStatus $advice){
        return $advice;
    }

    public function update(UpdateAdviceRequest $request, AdviceStatus $advicestatus){
        $advicestatus->fill($request->validated());
        $advicestatus->save();
        return $advice;
    }

    public function destroy(AdviceStatus $advicestatus){
        $advicestatus->delete();
        return response()->noContent();
    }
}
