<?php

namespace App\Http\Controllers;

use App\Data\GroupData;
use App\Models\Group;
use App\Models\Setting;
use App\Models\User;
use App\Services\CurrentGroupService;
use App\Services\SessionService;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PageController extends Controller
{
    // TODO move all the pages to their own controllers

    public function login()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        if (User::empty()) {
            return redirect()->route('register');
        }

        // Nur in lokaler Umgebung Benutzer laden
        if (app()->environment('local')) {
            $users = User::all()->map(fn (User $user) => [
                'id' => $user->uuid,
                'name' => $user->name,
                'email' => $user->email,
            ]);
        } else {
            $users = [];
        }

        return Inertia::render('LoginForm', [
            'devUsers' => $users,
        ]);
    }

    public function dashboard(CurrentGroupService $currentGroupService)
    {
        $currentGroup = $currentGroupService->getGroup();
        $advisorInfo = $currentGroup !== null ? $currentGroup->dashboard_info : null;
        $advisorInfo ??= Setting::get('advisorInfo');

        return Inertia::render('Dashboard', [
            'advisorInfo' => $advisorInfo,
        ]);
    }

    public function profile()
    {
        return Inertia::render('Profile');
    }

    public function users(SessionService $sessionService)
    {
        $canPromoteUsersToSystemAdmin = $sessionService->actsAsSystemAdmin();

        return Inertia::render('Users', [
            'canPromoteUsersToSystemAdmin' => $canPromoteUsersToSystemAdmin,
        ]);
    }

    public function settings()
    {
        return Inertia::render('Settings', [
            'settings' => Setting::all(),
        ]);
    }

    public function resetPassword()
    {
        /*if(auth()->check()){
            return redirect()->route('dashboard');
        }*/

        return Inertia::render('ResetPasswordForm');
    }

    public function changePassword(Request $request)
    {
        return Inertia::render('ChangePasswordForm', [
            'token' => $request->get('token'),
            'email' => $request->get('email'),
        ]);
    }

    public function newAdvice()
    {
        return Inertia::render('NewAdvice', [
            'groupId' => Group::latest()->first()?->uuid,
        ]);
    }

    public function impress()
    {
        return Inertia::render('HtmlContent', [
            'content' => Setting::get('impress'),
        ]);
    }

    public function dataPolicy()
    {
        return Inertia::render('HtmlContent', [
            'content' => Setting::get('datapolicy'),
        ]);
    }

    public function initiativeSelection(#[CurrentUser] User $user, SessionService $sessionService)
    {
        if (! $sessionService->isGroupMissing()) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('InitiativeSelection', [
            'initiatives' => $user->groups->map(fn ($group) => GroupData::fromModel($group)),
            'canActAsSystemAdmin' => $user->is_admin,
        ]);
    }
}
