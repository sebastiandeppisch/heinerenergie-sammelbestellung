<?php

namespace App\Http\Controllers;

use App\Data\GroupData;
use App\Models\Setting;
use App\Models\User;
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
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]);
        }

        return Inertia::render('LoginForm', [
            'devUsers' => $users,
        ]);
    }

    public function dashboard()
    {
        return Inertia::render('Dashboard', [
            'advisorInfo' => Setting::get('advisorInfo'),
        ]);
    }

    public function profile()
    {
        return Inertia::render('Profile');
    }

    public function users()
    {
        return Inertia::render('Users');
    }

    public function settings()
    {
        return Inertia::render('Settings');
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
        return Inertia::render('NewAdvice');
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
