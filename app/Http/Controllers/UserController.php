<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use maxh\Nominatim\Nominatim;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\SetAddressRequest;
use App\Http\Requests\SetPictureRequest;
use App\Http\Requests\UpdateUserRequest;
use Illuminate\Database\Eloquent\Builder;

class UserController extends Controller
{

    private function dxFilter(Request $request, Builder $builder): Builder{
        if(isset($request->searchOperation) && isset($request->searchValue) && isset($request->searchExpr)){
			if($request->searchOperation === "contains"){
				$builder = $builder->where($request->searchExpr, 'like', "%".$request->searchValue."%");
			}
		}
        return $builder;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return $this->dxFilter($request, User::query())->get();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreUserRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreUserRequest $request)
    {
        $user = new User($request->all());
        $user->password = '';
        $user->save();
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateUserRequest  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $user->fill($request->all());
        $user->save();
        return $user;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        $user->delete();
    }

    public function show(User $user){
        return $user;
    }

    public function picture(SetPictureRequest $request){
        $user = Auth::user();
        $user->picture = $request->url;
        $user->save();
        return $user;
    }
}
