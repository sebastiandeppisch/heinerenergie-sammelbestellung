<?php

namespace App\Http\Controllers;

use App\Models\Advice;
use App\Mail\SendOrderLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Http\Requests\StoreAdviceRequest;
use App\Http\Requests\UpdateAdviceRequest;
use App\Http\Resources\DataProtectedAdvice;

class AdviceController extends Controller
{
    public function __construct(){
        $this->authorizeResource(Order::class, 'advice');
    }

    public function index()
    {
        $isAdmin = Auth::user()->is_admin;
        return Advice::all()->filter(function(Advice $advice){
            return Auth::user()->can('viewDataProtected', $advice);
        })->values()->map( fn($advice) => new DataProtectedAdvice($advice) );
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

    public function sendOrderLink(Advice $advice){
        Mail::to($advice->email)->send(new SendOrderLink($advice));
        return response()->noContent(202);
    }

    public function assign(Advice $advice){
        if($advice->advisor_id === null){
            $advice->advisor_id = Auth::user()->id;
            $advice->save();
        }else{
            abort(403, "Diese Beratung wurde bereits einem Berater zugewiesen");
        }
        
        return $advice;
    }
}
