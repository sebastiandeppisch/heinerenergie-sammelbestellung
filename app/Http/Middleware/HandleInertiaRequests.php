<?php

namespace App\Http\Middleware;

use App\Data\GroupData;
use App\Models\Group;
use Illuminate\Http\Request;
use Inertia\Middleware;

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
    #[\Override]
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
    #[\Override]
    public function share(Request $request): array
    {
        $currentGroup = session()->get('actAsGroupId');
        $currentGroup = Group::find($currentGroup);

        if ($currentGroup) {
            $currentGroup = GroupData::fromModel($currentGroup);
        }

        $flashKeys = ['error', 'success', 'warning', 'info'];

        $flashMessages = [];

        foreach ($flashKeys as $flashKey) {
            if (session()->has($flashKey)) {
                $flashMessages[$flashKey] = session()->get($flashKey);
            }
        }

        return array_merge(parent::share($request), [
            'auth.user' => fn () => $request->user()?->only([
                'id',
                'first_name',
                'last_name',
                'name',
                'email',
        //        'is_admin',
                'is_acting_as_admin',
            ]),
            'auth.availableGroups' => fn () => $request->user()?->groups->map(fn (Group $group) => GroupData::fromModel($group)),
            'auth.currentGroup' => fn () => $currentGroup,
            'flashMessages' => $flashMessages,
        ]);
    }
}
