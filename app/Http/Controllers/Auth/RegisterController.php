<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Auth;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class RegisterController extends Controller
{
    public function show(): RedirectResponse|Response
    {
        if (User::count() > 0) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('RegisterForm');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $user = User::create($request->validated());

        $user->is_admin = true;
        $user->save();
        Auth::login($user);

        return redirect()->route('dashboard');
    }
}
