<?php

declare(strict_types=1);

namespace App\Providers;

use Closure;
use App\Actions\FetchCoordinateByAddress;
use App\Actions\FetchCoordinateByFreeText;
use App\Services\CurrentGroupService;
use App\ValueObjects\Address;
use App\ValueObjects\Coordinate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use maxh\Nominatim\Nominatim;
use Opcodes\LogViewer\Facades\LogViewer;
use Override;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    #[Override]
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        LogViewer::auth(fn ($request): bool => $request->user()?->email === config('app.admin_email'));

        if (config('app.env') === 'testing') {
            $this->app->bind(FetchCoordinateByAddress::class, function (): Closure {

                $coordinatesOfDarmstadtCenter = new Coordinate(
                    lat: 49.8728475,
                    lng: 8.6510204
                );

                return fn (Address $address): Coordinate => $coordinatesOfDarmstadtCenter;
            });

            $this->app->bind(FetchCoordinateByFreeText::class, function (): Closure {
                $coordinatesOfDarmstadtCenter = new Coordinate(
                    lat: 49.8728475,
                    lng: 8.6510204
                );

                return fn (string $text): Coordinate => $coordinatesOfDarmstadtCenter;
            });
        } else {
            $this->app->bind(function (): Nominatim {
                $url = 'http://nominatim.openstreetmap.org/';
                $defaultHeader = [
                    'User-Agent' => app_name().' CMS',
                ];

                return new Nominatim($url, $defaultHeader);
            });
        }

        $this->app->singleton(fn (): CurrentGroupService => new CurrentGroupService);

        Model::shouldBeStrict(! $this->app->isProduction());
    }
}
