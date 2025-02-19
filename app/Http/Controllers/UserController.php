<?php

namespace App\Http\Controllers;

use App\Actions\FetchCoordinateByAddress;
use App\Http\Requests\SetAddressRequest;
use App\Http\Requests\SetPictureRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Group;
use App\Models\User;
use App\Services\SessionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Log;

class UserController extends Controller
{
    public function __construct(
        private readonly SessionService $sessionService
    ) {
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
        $user = $this->user();

        if (! $user->can('actAsGroup', $group)) {
            Log::error('User is not in group', ['user' => $user->id, 'group' => $group->id]);

            return redirect()->back()->with('error', 'Du bist nicht in dieser Gruppe');
        }

        if ($request->boolean('asAdmin')) {
            if (! $user->can('actAsGroupAdmin', $group)) {
                Log::error('User is not a group admin', ['user' => $user->id, 'group' => $group->id]);

                return redirect()->back()->with('error', 'Du bist kein Gruppenadministrator');
            }
        }

        $this->sessionService->actAsGroup($group, $request->boolean('asAdmin'));

        return redirect()->back();
    }

    public function actAsSystemAdmin()
    {
        $this->sessionService->actAsSystemAdmin();

        return redirect()->back();
    }
}
