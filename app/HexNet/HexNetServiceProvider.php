<?php

declare(strict_types=1);

namespace App\HexNet;

use App\Events\MapPointCreatedByFormSubmission;
use App\HexNet\Console\HexNetDemoCommand;
use App\HexNet\Listeners\NotifyHexNetAdmins;
use App\HexNet\Listeners\PauseHexNetsWithoutCategories;
use App\HexNet\Listeners\PublishHexNetPoint;
use App\HexNet\Listeners\ShareHexNetHighlight;
use App\HexNet\Models\HexNet;
use App\HexNet\Observers\MapPointObserver;
use App\HexNet\Policies\HexNetPolicy;
use App\HexNet\Services\HexNetJitter;
use App\Models\MapPoint;
use App\Models\MapPointCategory;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * HexaNetz is a plugin on top of the map points. Removing this provider, its routes, its tables and the menu entry
 * removes it completely.
 */
class HexNetServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(HexNetJitter::class, fn (): HexNetJitter => new HexNetJitter((string) config('app.key')));
    }

    public function boot(): void
    {
        Gate::policy(HexNet::class, HexNetPolicy::class);
        MapPoint::observe(MapPointObserver::class);
        MapPointCategory::deleting(fn (MapPointCategory $category) => app(PauseHexNetsWithoutCategories::class)->handle($category));
        Event::listen(MapPointCreatedByFormSubmission::class, PublishHexNetPoint::class);
        Event::listen(MapPointCreatedByFormSubmission::class, ShareHexNetHighlight::class);
        Event::listen(MapPointCreatedByFormSubmission::class, NotifyHexNetAdmins::class);

        Route::middleware('web')->group(base_path('routes/hexnet.php'));

        if ($this->app->runningInConsole()) {
            $this->commands([HexNetDemoCommand::class]);
        }
    }
}
