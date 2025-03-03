<?php

namespace App\LaravelExtensions\StrictGates;

use Illuminate\Auth\AuthServiceProvider as BaseAuthServiceProvider;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;

class AuthServiceProvider extends BaseAuthServiceProvider
{
    protected function registerAccessGate()
    {
        $this->app->singleton(GateContract::class, function ($app) {

            return new Gate($app, fn () => call_user_func($app['auth']->userResolver()));
        });
    }
}
