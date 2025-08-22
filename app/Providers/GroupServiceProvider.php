<?php

namespace App\Providers;

use App\Context\GlobalGroupContext;
use App\Context\GroupContextContract;
use App\Services\SessionService;
use Illuminate\Support\ServiceProvider;
use Override;

class GroupServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this->app->singleton(SessionService::class);
        app()->bind(GroupContextContract::class, GlobalGroupContext::class);
    }
}
