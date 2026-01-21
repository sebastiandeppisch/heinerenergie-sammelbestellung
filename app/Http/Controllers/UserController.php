<?php

namespace App\Http\Controllers;

use App\Data\UserData;
use App\Http\Context\SessionGroupContextFactory;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Jobs\CacheUsersAdvicePolicies;
use App\Models\Group;
use App\Models\User;
use App\Notifications\PasswordChangedByAdmin;
use App\Services\SessionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class UserController extends Controller
{
    public function __construct(
        private readonly SessionService $sessionService
    ) {}

    public function user(): User
    {
        return Auth::user();
    }

    public function index()
    {
        $canPromoteUsersToSystemAdmin = $this->sessionService->actsAsSystemAdmin();

        $users = User::query()
            ->with('groups')
            ->get()
            ->filter(fn (User $user) => Auth::user()->can('view', $user))
            ->map(fn (User $user) => UserData::fromModel($user, false, true))
            ->values()
            ->all();

        return Inertia::render('Users', [
            'canPromoteUsersToSystemAdmin' => $canPromoteUsersToSystemAdmin,
            'users' => $users,
        ]);
    }

    public function store(StoreUserRequest $request)
    {
        $user = new User($request->validated());
        $user->password = '';

        if ($this->sessionService->actsAsSystemAdmin() && $request->has('is_admin')) {
            $user->is_admin = $request->boolean('is_admin');
        }

        $user->save();

        $group = $this->sessionService->getCurrentGroup();
        if ($group !== null) {
            $user->groups()->attach($group->id, ['is_admin' => false]);
        }

        return redirect()->route('users')->with('success', 'Berater*in wurde erfolgreich erstellt');
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $user->fill($request->validated());

        if ($this->sessionService->actsAsSystemAdmin() && $request->has('is_admin')) {
            $user->is_admin = $request->boolean('is_admin');
        }

        $user->save();

        return redirect()->route('users')->with('success', 'Berater*in wurde erfolgreich aktualisiert');
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

    public function changePassword(ChangePasswordRequest $request, User $user)
    {
        $user->password = $request->input('password');
        $user->save();

        $user->notify(new PasswordChangedByAdmin);

        return redirect()->back()->with('success', 'Passwort erfolgreich geändert');
    }
}
