<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CheckSysAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request):((Response|RedirectResponse))  $next
     * @return Response|RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (! $request->user()) {
            throw new AuthorizationException('You are not a sysadmin.');
        }
        $mail = $request->user()->email;

        $adminEmail = config('app.admin_email');

        if ($mail !== $adminEmail) {
            throw new AuthorizationException('You are not a sysadmin.');
        }

        return $next($request);
    }
}
