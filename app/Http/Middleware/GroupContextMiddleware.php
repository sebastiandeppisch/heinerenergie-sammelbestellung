<?php

namespace App\Http\Middleware;

use App\Context\GroupContextContract;
use App\Context\NoGroupContext;
use App\Http\Context\SessionGroupContextFactory;
use Closure;
use Illuminate\Http\Request;

class GroupContextMiddleware
{
    public function __construct(
        private readonly SessionGroupContextFactory $factory,
    ) {}

    public function handle(Request $request, Closure $next)
    {
        if (app(GroupContextContract::class) instanceof NoGroupContext) {
            app()->instance(
                GroupContextContract::class,
                $this->factory->createFromSession()
            );
        }

        return $next($request);
    }
}
