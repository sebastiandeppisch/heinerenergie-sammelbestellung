<?php

namespace App\Http\Context;

use App\Context\GroupContext;
use App\Context\GroupContextContract;
use App\Models\User;
use App\Services\SessionService;
use Illuminate\Support\Facades\Auth;

class SessionGroupContextFactory
{
    public function __construct(
        private readonly SessionService $sessionService
    ) {}

    public function createFromSession(?User $user = null): GroupContextContract
    {
        $user ??= Auth::user();

        return new GroupContext(
            currentGroup: $this->sessionService->getCurrentGroup(),
            isActingAsSystemAdmin: $this->sessionService->actsAsSystemAdmin(),
            user: $user,
            isActingAsGroupAdmin: $this->sessionService->actsAsGroupAdmin()
        );
    }
}
