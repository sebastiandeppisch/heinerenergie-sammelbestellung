<?php

namespace App\Http\Middleware;

use App\Data\GroupBaseData;
use App\Data\GroupData;
use App\Data\UserData;
use App\Models\Group;
use App\Services\CurrentGroupService;
use App\Services\SessionService;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Override;

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
    #[Override]
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
    #[Override]
    public function share(Request $request): array
    {

        $flashKeys = ['error', 'success', 'warning', 'info'];

        $flashMessages = [];

        foreach ($flashKeys as $flashKey) {
            if (session()->has($flashKey)) {
                $flashMessages[$flashKey] = session()->get($flashKey);
            }
        }

        return array_merge(parent::share($request), [
            'auth.user' => fn () => $this->getUserData($request),
            'auth.availableGroups' => fn () => $request->user()?->groups->map(fn (Group $group) => GroupData::fromModel($group)),
            'auth.currentGroup' => fn () => app(CurrentGroupService::class)->getGroup() ? GroupBaseData::fromModel(app(CurrentGroupService::class)->getGroup()) : null,
            'flashMessages' => $flashMessages,
            'defaultLogo' => app_logo(),
            'appName' => app_name(...),
            'userRole' => $this->getUserRole(...),
        ]);
    }

    private function getUserRole(): string
    {
        if ($this->sessionService()->actsAsGroupAdmin()) {
            return 'group-admin';
        }

        if ($this->sessionService()->actsAsSystemAdmin()) {
            return 'system-admin';
        }

        return 'user';
    }

    private function sessionService(): SessionService
    {
        return app(SessionService::class);
    }

    private function getUserData(Request $request): ?UserData
    {
        $userIsActingAsAdmin = $this->sessionService()->actsAsSystemAdmin() || $this->sessionService()->actsAsGroupAdmin();

        return $request->user() ? UserData::fromModel($request->user()->fresh(), $userIsActingAsAdmin) : null;
    }
}
