<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSettingRequest;
use App\Models\Setting;
use Illuminate\Http\Response;

class SettingController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        return Setting::all();
    }

    /**
     * Display the specified resource.
     *
     * @param  Setting  $setting
     * @return Response
     */
    public function show(string $key)
    {
        return Setting::where('key', $key)->firstOrFail();
    }

    /**
     * Update the specified resource in storage.
     *
     * @return Response
     */
    public function update(UpdateSettingRequest $request, Setting $setting)
    {
        $setting->fill($request->all());
        $setting->save();

        return $setting->fresh();
    }

    public function advisorInfo()
    {
        return [
            'html' => Setting::get('advisorInfo'),
        ];
    }
}
