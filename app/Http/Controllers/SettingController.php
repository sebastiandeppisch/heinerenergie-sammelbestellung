<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Http\Resources\SettingResource;
use App\Http\Requests\StoreSettingRequest;
use App\Http\Requests\RequireOrderPassword;
use App\Http\Requests\UpdateSettingRequest;

class SettingController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Setting::all();
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Setting  $setting
     * @return \Illuminate\Http\Response
     */
    public function show(string $key)
    {
        return Setting::where('key', $key)->firstOrFail();
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateSettingRequest  $request
     * @param  \App\Models\Setting  $setting
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateSettingRequest $request, Setting $setting)
    {
        $setting->fill($request->all());
        $setting->save();
    }

    public function impress(){
        return [
            "html" => Setting::get('impress')
        ];
    }

    public function datapolicy(){
        return [
            "html" => Setting::get('datapolicy')
        ];
    }

    public function orderFormText(RequireOrderPassword $request){
        return [
            "html" => Setting::get('orderFormText')
        ];
    }

    public function advisorInfo(){
        return [
            "html" => Setting::get('advisorInfo')
        ];
    }
}
