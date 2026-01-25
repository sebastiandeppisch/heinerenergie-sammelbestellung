<?php

namespace App\Http\Controllers\Api;

use App\Actions\FetchCoordinateByAddress;
use App\Data\UserData;
use App\Http\Controllers\Controller;
use App\Http\Requests\SetAddressRequest;
use App\Http\Requests\SetPictureRequest;
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

    public function index(Request $request)
    {

        if ($request->has(('withoutself'))) {
            $query = User::query()->where('id', '!=', $this->user()->id);
        } else {
            $query = User::query();
        }

        $users = $this->dxFilter($request, $query)->get();

        // @phpstan-ignore-next-line
        return $users->filter(fn (User $user) => $this->user()->can('view', $user))->values()->map(fn ($user) => UserData::fromModel($user, false));
    }

    public function show(User $user)
    {
        return UserData::fromModel($user, true);
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
