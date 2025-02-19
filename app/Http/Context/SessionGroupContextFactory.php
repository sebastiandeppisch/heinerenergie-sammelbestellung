<?php

namespace App\Http\Context;

use App\Models\User;
use App\Context\GroupContext;
use App\Context\GroupContextContract;
use App\Services\SessionService;
use Illuminate\Support\Facades\Auth;

class SessionGroupContextFactory
{
    public function __construct(
        private readonly SessionService $sessionService
    ) {}

    public function createFromSession(?User $user = null): GroupContextContract
    {
        $user = $user ?? Auth::user();
        
        return new GroupContext(
            currentGroup: $this->sessionService->getCurrentGroup(),
            actsAsSystemAdmin: $this->sessionService->actsAsSystemAdmin(),
            user: $user,
            actsAsGroupAdmin: $this->sessionService->actsAsGroupAdmin()
        );
    }
} 