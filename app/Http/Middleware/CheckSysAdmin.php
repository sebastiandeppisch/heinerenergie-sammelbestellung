<?php

namespace App\Http\Middleware;

use App\Context\GroupContextContract;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CheckSysAdmin
{
    public function __construct(
        private readonly GroupContextContract $groupContext
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request):((Response|RedirectResponse))  $next
     * @return Response|RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user) {
            throw new AuthorizationException('You are not a sysadmin.');
        }

        if (! $this->groupContext->isActingAsSystemAdmin($user)) {
            throw new AuthorizationException('You are not a sysadmin.');
        }

        return $next($request);
    }
}
