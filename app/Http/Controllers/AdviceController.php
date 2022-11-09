<?php

namespace App\Http\Controllers;

use App\Models\Advice;
use Illuminate\Http\Request;
use App\Http\Requests\StoreAdviceRequest;
use App\Http\Requests\UpdateAdviceRequest;

class AdviceController extends Controller
{
    public function index()
    {
        return Advice::all();
    }

    public function store(StoreAdviceRequest $request){
        $advice = new Advice();
        $advice->fill($request->validated());
        $advice->save();
        return $advice;
    }

    public function show(Advice $advice){
        return $advice;
    }

    public function update(UpdateAdviceRequest $request, Advice $advice){
        $advice->fill($request->validated());
        $advice->save();
        return $advice;
    }

    public function destroy(Advice $advice){
        $advice->delete();
        return response()->noContent();
    }
}
