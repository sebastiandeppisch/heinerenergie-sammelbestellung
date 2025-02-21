<?php

namespace App\Providers;

use App\Context\GroupContextContract;
use App\Context\NoGroupContext;
use App\Services\SessionService;
use Illuminate\Support\ServiceProvider;

class GroupServiceProvider extends ServiceProvider
{
    #[\Override]
    public function register(): void
    {
        $this->app->singleton(SessionService::class);

        $this->app->bind(GroupContextContract::class, NoGroupContext::class);
    }
}
