<?php

namespace App\Providers;

use App\Context\NoGroupContext;
use App\Services\SessionService;
use App\Context\GlobalGroupContext;
use App\Context\GroupContextContract;
use Illuminate\Support\ServiceProvider;

class GroupServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register SessionService as singleton
        $this->app->singleton(SessionService::class);

        $this->app->bind(GroupContextContract::class, GlobalGroupContext::class);

        //$this->app->bind(GroupContextContract::class, NoGroupContext::class);
    }
}
