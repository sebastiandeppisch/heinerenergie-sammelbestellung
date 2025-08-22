<?php

namespace App\Http\Controllers\Api;

use App\Actions\FetchCoordinateByAddress;
use App\Http\Controllers\Controller;
use App\Http\Requests\SetAddressRequest;
use App\Http\Requests\SetPictureRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        if ($request->has(('withoutself'))) {
            $query = User::query()->where('id', '!=', $this->user()->id);
        } else {
            $query = User::query();
        }

        $users = $this->dxFilter($request, $query)->get();

        return $users->filter(fn (User $user) => $this->user()->can('view', $user))->values();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(StoreUserRequest $request)
    {
        $user = new User($request->validated());
        $user->password = '';
        $user->save();

        return $user;
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

        return response()->noContent();
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
}
