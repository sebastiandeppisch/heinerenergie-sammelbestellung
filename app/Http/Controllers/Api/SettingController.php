<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSettingRequest;
use App\Models\Setting;

class SettingController extends Controller
{
    public function index()
    {
        return Setting::all();
    }

    public function show(string $key)
    {
        return Setting::where('key', $key)->firstOrFail();
    }

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
