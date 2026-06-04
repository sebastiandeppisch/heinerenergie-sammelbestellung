<?php

namespace App\Http\Controllers\Auth;

use App\Http\Context\SessionGroupContextFactory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Jobs\CacheUsersAdvicePolicies;
use App\Services\UserEncryptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;

class AuthenticatedSessionController extends Controller
{
    public function __construct(private readonly UserEncryptionService $encryptionService) {}

    public function store(LoginRequest $request)
    {
        $request->authenticate();

        $request->session()->regenerate();

        $this->issueEncryptionKey($request->input('password'), $request);

        $groupContext = app(SessionGroupContextFactory::class)->createFromSession();
        CacheUsersAdvicePolicies::dispatchAfterResponse(Auth::user(), $groupContext);

        return Auth::user();
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        Cookie::queue(Cookie::forget('enc_key'));

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

    private function issueEncryptionKey(string $password, Request $request): void
    {
        $salt = base64_encode(random_bytes(32));
        $key = $this->encryptionService->deriveKey($password, $salt);

        $request->session()->put('enc_salt', $salt);

        Cookie::queue(
            Cookie::make('enc_key', $key, 0, '/', null, true, true, false, 'Strict')
        );
    }
}
