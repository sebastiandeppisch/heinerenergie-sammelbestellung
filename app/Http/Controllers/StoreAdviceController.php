<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAdviceRequest;
use App\Mail\AdviceCreated;
use App\Models\Advice;
use Illuminate\Support\Facades\Mail;

class StoreAdviceController extends Controller
{
    public function __invoke(StoreAdviceRequest $request)
    {
        $advice = new Advice;
        $advice->fill($request->validated());
        $advice->save();

        Mail::to($advice->email)->send(new AdviceCreated($advice));

        return response()->json($advice);
    }
}
