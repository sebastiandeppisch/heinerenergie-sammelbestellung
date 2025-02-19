<?php

namespace App\Http\Middleware;

use App\Models\Group;
use App\Data\GroupData;
use Inertia\Middleware;
use Illuminate\Http\Request;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $currentGroup = session()->get('actAsGroupId');
        $currentGroup = Group::find($currentGroup);

        return array_merge(parent::share($request), [
            'auth.user' => fn () => $request->user()?->only([
                'id',
                'first_name',
                'last_name',
                'name',
                'email',
                'is_admin',
                'is_acting_as_admin',
                'is_admin'
            ]),
            'auth.availableGroups' => fn () => $request->user()?->groups->map(fn (Group $group) => GroupData::fromModel($group)),
            'auth.currentGroup' => fn () => GroupData::fromModel($currentGroup),
        ]);
    }
}
