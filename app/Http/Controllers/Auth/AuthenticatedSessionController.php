<?php

namespace App\Http\Controllers\Auth;

use App\Http\Context\SessionGroupContextFactory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Jobs\CacheUsersAdvicePolicies;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticatedSessionController extends Controller
{
    public function store(LoginRequest $request)
    {
        $request->authenticate();

        $request->session()->regenerate();

        $groupContext = app(SessionGroupContextFactory::class)->createFromSession();
        CacheUsersAdvicePolicies::dispatchAfterResponse(Auth::user(), $groupContext);

        return Auth::user();
    }

    /**
     * Destroy an authenticated session.
     *
     * @return RedirectResponse
     */
    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        if ($user === null) {
            return [
                'isLoggedIn' => false,
                'user' => null,
            ];
        }

        return [
            'isLoggedIn' => true,
            'user' => $user,
        ];
    }
}
