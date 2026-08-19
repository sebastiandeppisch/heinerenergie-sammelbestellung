<?php

namespace App\Providers;

use App\Actions\FetchAddressByCoordinate;
use App\Actions\FetchCoordinateByAddress;
use App\Actions\FetchCoordinateByFreeText;
use App\Contracts\MailCredentialsRepository;
use App\Contracts\MailServiceContract;
use App\Contracts\NextcloudFileClientContract;
use App\Contracts\NextcloudUserClientContract;
use App\Nextcloud\NextcloudUserClient;
use App\Nextcloud\WebDavNextcloudFileClient;
use App\Repositories\SessionMailCredentialsRepository;
use App\Services\CurrentGroupService;
use App\Services\MailService;
use App\ValueObjects\Address;
use App\ValueObjects\Coordinate;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use maxh\Nominatim\Nominatim;
use Opcodes\LogViewer\Facades\LogViewer;
use Override;
use Tests\Support\MockNextcloudFileClient;
use Tests\Support\MockNextcloudUserClient;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    #[Override]
    public function register(): void
    {
        $this->app->bind(
            MailCredentialsRepository::class,
            SessionMailCredentialsRepository::class,
        );

        $this->app->bind(
            MailServiceContract::class,
            MailService::class,
        );

        $this->app->singleton(
            NextcloudFileClientContract::class,
            config('nextcloud.base_url') ? WebDavNextcloudFileClient::class : MockNextcloudFileClient::class
        );

        $this->app->singleton(
            NextcloudUserClientContract::class,
            config('nextcloud.base_url') ? NextcloudUserClient::class : MockNextcloudUserClient::class
        );
    }

    /**
     * Bootstrap any application services.
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

            $this->app->bind(FetchAddressByCoordinate::class, fn (): Closure => fn (Coordinate $coordinate): string => 'Musterstraße 1, 64283 Darmstadt, Deutschland');
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
