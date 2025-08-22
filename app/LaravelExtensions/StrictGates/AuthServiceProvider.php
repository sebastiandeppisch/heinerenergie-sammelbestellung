<?php

namespace App\LaravelExtensions\StrictGates;

use Illuminate\Auth\AuthServiceProvider as BaseAuthServiceProvider;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;

class AuthServiceProvider extends BaseAuthServiceProvider
{
    #[\Override]
    protected function registerAccessGate()
    {
        $this->app->singleton(fn($app): \Illuminate\Contracts\Auth\Access\Gate => new Gate($app, fn () => call_user_func($app['auth']->userResolver())));
    }
}
