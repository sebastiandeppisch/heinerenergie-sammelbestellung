<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\FetchCoordinateByAddress;
use App\Data\UserData;
use App\Http\Controllers\Controller;
use App\Http\Requests\SetAddressRequest;
use App\Http\Requests\SetPictureRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
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

    /**
     * @param  Builder<User>  $builder
     * @return Builder<User>
     */
    private function dxFilter(Request $request, Builder $builder): Builder
    {
        if (isset($request->searchOperation) && isset($request->searchValue) && isset($request->searchExpr)) {
            if ($request->searchOperation === 'contains') {
                $builder = $builder->where($request->searchExpr, 'like', '%'.$request->searchValue.'%');
            }
        }

        return $builder;
    }

    public function index(Request $request): JsonResponse
    {

        if ($request->has(('withoutself'))) {
            $query = User::query()->where('id', '!=', $this->user()->id);
        } else {
            $query = User::query();
        }

        $users = $this->dxFilter($request, $query)->get();

        return response()->json(
            $users->filter(fn (User $user): bool => $this->user()->can('view', $user))->values()->map(fn (User $user): UserData => UserData::fromModel($user, false))
        );
    }

    public function show(User $user): UserData
    {
        return UserData::fromModel($user, true);
    }

    public function picture(SetPictureRequest $request): JsonResponse
    {
        $user = $this->user();
        $user->picture = $request->url;
        $user->save();

        return response()->json($user);
    }

    public function address(SetAddressRequest $request): JsonResponse
    {
        $user = $this->user();
        $user->fill($request->validated());
        $user->save();
        $this->fetchCoordinates($user);

        return response()->json($user);
    }

    private function fetchCoordinates(User $user): void
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
