<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DevLoginController extends Controller
{
    public function __construct(
    ) {
        if (! app()->environment('local')) {
            abort(404);
        }
    }

    public function login(User $user, Request $request)
    {

        $session = $request->session();
        $session->flush();

        Auth::login($user);

        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }
}
