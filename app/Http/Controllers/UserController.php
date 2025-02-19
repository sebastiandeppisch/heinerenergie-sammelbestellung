<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Group;
use Illuminate\Http\Request;
use maxh\Nominatim\Nominatim;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\SetAddressRequest;
use App\Http\Requests\SetPictureRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Actions\FetchCoordinateByAddress;
use Illuminate\Database\Eloquent\Builder;

class UserController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(User::class, 'user');
    }

    private function user(): User
    {
        return Auth::user();
    }

    private function dxFilter(Request $request, Builder $builder): Builder
    {
        if (isset($request->searchOperation) && isset($request->searchValue) && isset($request->searchExpr)) {
            if ($request->searchOperation === 'contains') {
                $builder = $builder->where($request->searchExpr, 'like', '%'.$request->searchValue.'%');
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
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        $user->delete();
    }

    public function show(User $user)
    {
        return $user;
    }

    public function picture(SetPictureRequest $request)
    {
        $user = $this->user();
        $user->picture = $request->url;
        $user->save();

        return $user;
    }

    public function address(SetAddressRequest $request)
    {
        $user = $this->user();
        $user->fill($request->validated());
        $user->save();
        $this->fetchCoordinates($user);

        return $user;
    }

    private function fetchCoordinates(User $user)
    {
        if ($user->address === null) {
            $user->coordinate = null;
            $user->save();
            return;
        }

        $user->coordinate = app(FetchCoordinateByAddress::class)($user->address);
        $user->save();
    }

    public function actAsGroup(Request $request, Group $group)
    {
        session()->put('actAsGroupId', $group->id);
        session()->put('actAsGroupAdmin', $request->asAdmin);
        return redirect()->back();
    }

    public function actAsSystemAdmin()
    {
        if ($this->user()->is_admin === false) {
            abort(403, 'Du hast keine Berechtigung um als Administrator zu agieren.');
        }
        session()->put('actAsSystemAdmin', true);
    }
}
