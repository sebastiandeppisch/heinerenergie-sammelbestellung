<?php

namespace App\Http\Middleware;

use App\Context\GroupContextContract;
use App\Http\Context\SessionGroupContextFactory;
use Closure;
use Config;
use Illuminate\Http\Request;

class GroupContextMiddleware
{
    public function __construct(
        private readonly SessionGroupContextFactory $factory,
    ) {}

    public function handle(Request $request, Closure $next)
    {
        $contextConfig = Config::get('app.group_context');
        if ($contextConfig === 'group') {
            app()->instance(
                GroupContextContract::class,
                $this->factory->createFromSession()
            );
        }

        return $next($request);
    }
}
