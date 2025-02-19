<?php

namespace App\Context;

use App\Models\User;
use App\Services\SessionService;
use Illuminate\Support\Facades\Auth;

class GroupContextFactory
{
    public function __construct(
        private readonly SessionService $sessionService
    ) {}

    public function create(?User $user = null): GroupContextContract
    {
        $user ??= Auth::user();
        
        return new GroupContext(
            currentGroup: $this->sessionService->getCurrentGroup(),
            actsAsSystemAdmin: $this->sessionService->actsAsSystemAdmin(),
            user: $user,
            actsAsGroupAdmin: $this->sessionService->actsAsGroupAdmin()
        );
    }
} 