<?php

namespace App\Http\Controllers;

use App\Http\Context\SessionGroupContextFactory;
use App\Jobs\CacheUsersAdvicePolicies;
use App\Models\Group;
use App\Models\User;
use App\Services\SessionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    public function __construct(
        private readonly SessionService $sessionService
    ) {}

    public function user(): User
    {
        return Auth::user();
    }

    public function actAsGroup(Request $request, Group $group)
    {
        $user = $this->user();

        $asAdmin = $request->boolean('asAdmin');

        if (! $user->can('actAsGroup', $group)) {
            Log::error('User is not in group', ['user' => $user->id, 'group' => $group->id]);
            $this->sessionService->clear();

            return redirect()->back()->withErrors('error', 'Du bist nicht in dieser Gruppe');
        }

        if ($asAdmin) {
            if (! $user->can('actAsGroupAdmin', $group)) {
                Log::error('User is not a group admin', ['user' => $user->id, 'group' => $group->id]);
                $this->sessionService->clear();

                return redirect()->back()->withErrors('error', 'Du bist kein Gruppenadministrator');
            }
        }

        $this->sessionService->actAsGroup($group, $asAdmin);

        $groupContext = app(SessionGroupContextFactory::class)->createFromSession();
        CacheUsersAdvicePolicies::dispatchAfterResponse($user, $groupContext);

        if ($redirectTo = $this->sessionService->getRedirectAfterSelectionAndForget()) {
            return redirect()->to($redirectTo);
        }

        Log::info('User is acting as group', ['user' => $user->id, 'group' => $group->id]);

        return redirect()->back()->with('success', 'Du agierst jetzt als Gruppe '.$group->name);
    }

    public function actAsSystemAdmin()
    {
        $user = $this->user();

        if (! $user->isGlobalAdmin()) {
            Log::error('User is not a system admin', ['user' => $user->id]);
            $this->sessionService->clear();

            return redirect()->back()->withErrors(['error' => 'Du bist kein Systemadministrator']);
        }

        $this->sessionService->actAsSystemAdmin();

        $groupContext = app(SessionGroupContextFactory::class)->createFromSession();
        CacheUsersAdvicePolicies::dispatchAfterResponse($user, $groupContext);

        if ($redirectTo = $this->sessionService->getRedirectAfterSelectionAndForget()) {
            return redirect()->to($redirectTo);
        }

        return redirect()->back()->with('success', 'Du agierst jetzt als Systemadministrator');
    }
}
