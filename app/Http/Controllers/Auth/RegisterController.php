<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Services\UserEncryptionService;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Inertia\Inertia;

class RegisterController extends Controller
{
    public function __construct(private readonly UserEncryptionService $encryptionService) {}

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

        $user->is_admin = true;
        $user->save();
        Auth::login($user);

        $this->issueEncryptionKey($request->input('password'), $request);

        return redirect()->route('dashboard');
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
