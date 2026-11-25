<?php

declare(strict_types=1);

namespace App\Providers;

use App\Http\Controllers\PublicFileController;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Override;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/dashboard';

    /**
     * Define your route model bindings, pattern filters, etc.
     */
    #[Override]
    public function boot(): void
    {
        $this->configureRateLimiting();

        $this->routes(function (): void {
            Route::prefix('api')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/web.php'));

            // Without session and cookies, so browsers and proxies may cache the files.
            Route::get('storage/{path}', PublicFileController::class)->where('path', '.*')->name('public-file');
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)->by(optional($request->user())->id ?: $request->ip()));

        RateLimiter::for('form-submit', function (Request $request) {
            if ($request->isPrecognitive()) {
                return Limit::none();
            }

            return Limit::perMinute(10)->by($request->ip());
        });

        // Generous, because a popup or table of the public map loads many thumbnails at once.
        RateLimiter::for('map-point-images', fn (Request $request) => Limit::perMinute(300)->by($request->ip()));
        RateLimiter::for('map-contact', function (Request $request) {
            if ($request->isPrecognitive()) {
                return Limit::none();
            }

            return Limit::perMinute(20)->by($request->ip());
        });
    }
}
