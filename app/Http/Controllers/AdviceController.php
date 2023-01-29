<?php

namespace App\Http\Controllers;

use App\Models\Advice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StoreAdviceRequest;
use App\Http\Requests\UpdateAdviceRequest;

class AdviceController extends Controller
{
    public function __construct(){
        $this->authorizeResource(Order::class, 'advice');
    }

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

    public function setAdvisors(Advice $advice, Request $request){
        $this->auth($advice, 'addAdvisors');
        $advice->shares()->sync($request->advisors);
    }

    private function auth(Advice $advice, string $ability){
        if(! Auth::user()->can($ability, $advice)){
            abort(403, "Du hast keine Berechtigung, diese Beratung zu sehen");
        }
    }
}
