<?php

namespace App\Http\Middleware;

use App\Context\GroupContextContract;
use App\Http\Context\SessionGroupContextFactory;
use App\Models\User;
use App\Services\SessionService;
use Closure;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\Request;

class GroupContextMiddleware
{
    public function __construct(
        private readonly SessionGroupContextFactory $factory,
        private readonly SessionService $sessionService,
        #[CurrentUser] private readonly ?User $user
    ) {}

    public function handle(Request $request, Closure $next)
    {
        if ($this->user && $this->sessionService->isGroupMissing()) {
            $groups = $this->user->groups;

            $whitelistedRoutes = [
                'initiatives.select',
                'actAsGroup',
                'login',
                'reset-password',
                'actAsSystemAdmin',
            ];

            if ($groups->count() === 1) {
                // If user has exactly one group, auto-select it
                $this->sessionService->actAsGroup($groups->first());
            } elseif (! in_array($request->route()->getName(), $whitelistedRoutes)) {
                // Store the current URL for redirect after selection
                $this->sessionService->setRedirectAfterSelection($request->url());

                return redirect()->route('initiatives.select');
            }
        }

        /*app()->instance(
            GroupContextContract::class,
            $this->factory->createFromSession()
        );*/

        return $next($request);
    }
}
