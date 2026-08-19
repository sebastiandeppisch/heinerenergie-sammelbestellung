<?php

declare(strict_types=1);

namespace App\LaravelExtensions\StrictGates;

use Illuminate\Auth\AuthServiceProvider as BaseAuthServiceProvider;
use Override;

class AuthServiceProvider extends BaseAuthServiceProvider
{
    #[Override]
    protected function registerAccessGate()
    {
        $this->app->singleton(fn ($app): \Illuminate\Contracts\Auth\Access\Gate => new Gate($app, fn (): mixed => call_user_func($app['auth']->userResolver())));
    }
}
