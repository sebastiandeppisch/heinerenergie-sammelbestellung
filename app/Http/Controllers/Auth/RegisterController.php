<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Auth;
use Inertia\Inertia;

class RegisterController extends Controller
{
    public function show()
    {
        if (User::count() > 0) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('RegisterForm');
    }

    public function store(RegisterRequest $request)
    {
        $user = User::create($request->validated());
        Auth::login($user);

        return redirect()->route('dashboard');
    }
}
