<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Context\GroupContextContract;
use App\Context\GroupContextInterface;
use App\Http\Context\SessionGroupContextFactory;

class GroupContextMiddleware
{
    public function __construct(
        private readonly SessionGroupContextFactory $factory
    ) {}

    public function handle(Request $request, Closure $next)
    {

        /*
        TODO use the group context
        
        app()->instance(
            GroupContextContract::class,
            $this->factory->createFromSession()
        );*/

        return $next($request);
    }
} 