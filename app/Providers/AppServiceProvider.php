<?php

declare(strict_types=1);

namespace App\Providers;

use App\Actions\FetchAddressByCoordinate;
use App\Actions\FetchCoordinateByAddress;
use App\Actions\FetchCoordinateByFreeText;
use App\Contracts\MailCredentialsRepository;
use App\Contracts\MailServiceContract;
use App\Contracts\NextcloudFileClientContract;
use App\Contracts\NextcloudUserClientContract;
use App\Nextcloud\NextcloudConfig;
use App\Nextcloud\NextcloudUserClient;
use App\Nextcloud\WebDavNextcloudFileClient;
use App\Repositories\SessionMailCredentialsRepository;
use App\Services\CurrentGroupService;
use App\Services\MailService;
use App\Services\NominatimThrottle;
use App\ValueObjects\Address;
use App\ValueObjects\Coordinate;
use Closure;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use maxh\Nominatim\Nominatim;
use Opcodes\LogViewer\Facades\LogViewer;
use Override;
use Psr\Http\Message\RequestInterface;
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

        $nextcloudConfigured = (new NextcloudConfig)->isConfigured();

        $this->app->singleton(
            NextcloudFileClientContract::class,
            $nextcloudConfigured ? WebDavNextcloudFileClient::class : MockNextcloudFileClient::class
        );

        $this->app->singleton(
            NextcloudUserClientContract::class,
            $nextcloudConfigured ? NextcloudUserClient::class : MockNextcloudUserClient::class
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
            $this->app->singleton(fn (): Nominatim => $this->makeNominatim());
        }

        $this->app->singleton(fn (): NominatimThrottle => new NominatimThrottle(
            interval: (float) config('services.nominatim.interval'),
            maxWait: (float) config('services.nominatim.max_wait'),
        ));

        $this->app->singleton(fn (): CurrentGroupService => new CurrentGroupService);

        Model::shouldBeStrict(! $this->app->isProduction());
    }

    /**
     * Builds the Nominatim client with its own Guzzle client, so that both the
     * timeouts and the rate limit apply to every request. The package default
     * would be a 30 second timeout and a misspelled connection timeout that
     * Guzzle silently ignores.
     */
    private function makeNominatim(): Nominatim
    {
        $url = (string) config('services.nominatim.url');

        $stack = HandlerStack::create();
        $stack->push($this->throttleNominatimRequests(), 'nominatim-throttle');

        $client = new Client([
            'base_uri' => $url,
            'connect_timeout' => (float) config('services.nominatim.connect_timeout'),
            'timeout' => (float) config('services.nominatim.timeout'),
            'handler' => $stack,
        ]);

        return new Nominatim($url, ['User-Agent' => $this->nominatimUserAgent()], $client);
    }

    /**
     * Guzzle middleware that waits for a free rate limit slot before each
     * request. The throttle is resolved per request so that tests can swap it
     * out even though the client itself is a singleton.
     */
    private function throttleNominatimRequests(): Closure
    {
        return fn (callable $handler): Closure => function (RequestInterface $request, array $options) use ($handler) {
            app(NominatimThrottle::class)->await();

            return $handler($request, $options);
        };
    }

    /**
     * The OpenStreetMap usage policy requires an identifiable application and a
     * contact address, otherwise it blocks the client.
     */
    private function nominatimUserAgent(): string
    {
        $contact = config('services.nominatim.contact');

        return app_name().' CMS'.(is_string($contact) && $contact !== '' ? ' ('.$contact.')' : '');
    }
}
