<?php

declare(strict_types=1);

namespace App\Providers;

use App\Actions\FetchAddressByCoordinate;
use App\Actions\FetchCoordinateByAddress;
use App\Actions\FetchCoordinateByFreeText;
use App\Services\NominatimThrottle;
use App\ValueObjects\Address;
use App\ValueObjects\Coordinate;
use Closure;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use Illuminate\Support\ServiceProvider;
use maxh\Nominatim\Nominatim;
use Psr\Http\Message\RequestInterface;
use RuntimeException;

/**
 * Wires up everything that talks to OpenStreetMap.
 *
 * In the testing environment the HTTP client is replaced by one that always
 * fails, so a test can never reach the real API by accident. Tests that need a
 * result bind the Nominatim instance or the geocoding actions themselves.
 */
class GeocodingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(fn (): NominatimThrottle => new NominatimThrottle(
            interval: (float) config('services.nominatim.interval'),
            maxWait: (float) config('services.nominatim.max_wait'),
        ));

        $this->app->singleton(fn (): Nominatim => $this->makeNominatim());

        if ($this->app->environment('testing')) {
            $this->bindTestingStubs();
        }
    }

    /**
     * Most tests only care that an advice ends up somewhere on the map, not
     * about the lookup itself. These stubs keep them from having to mock
     * Nominatim, while the refusing HTTP client above still guards the tests
     * that do go through the real actions.
     */
    private function bindTestingStubs(): void
    {
        $darmstadtCenter = new Coordinate(lat: 49.8728475, lng: 8.6510204);

        $this->app->bind(
            FetchCoordinateByAddress::class,
            fn (): Closure => fn (Address $address): Coordinate => $darmstadtCenter
        );

        $this->app->bind(
            FetchCoordinateByFreeText::class,
            fn (): Closure => fn (string $text): Coordinate => $darmstadtCenter
        );

        $this->app->bind(
            FetchAddressByCoordinate::class,
            fn (): Closure => fn (Coordinate $coordinate): string => 'Musterstraße 1, 64283 Darmstadt, Deutschland'
        );
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

        if ($this->app->environment('testing')) {
            // A handler that refuses everything. The safety net that keeps the
            // test suite from ever reaching the real OpenStreetMap servers.
            $stack = HandlerStack::create(new MockHandler([
                new RuntimeException('Nominatim must not be called in the testing environment. Bind Nominatim::class or the geocoding action in your test.'),
            ]));
        } else {
            $stack = HandlerStack::create();
            $stack->push($this->throttleRequests(), 'nominatim-throttle');
        }

        $client = new Client([
            'base_uri' => $url,
            'connect_timeout' => (float) config('services.nominatim.connect_timeout'),
            'timeout' => (float) config('services.nominatim.timeout'),
            'handler' => $stack,
        ]);

        return new Nominatim($url, ['User-Agent' => $this->userAgent()], $client);
    }

    /**
     * Guzzle middleware that waits for a free rate limit slot before each
     * request. The throttle is resolved per request so that tests can swap it
     * out even though the client itself is a singleton.
     */
    private function throttleRequests(): Closure
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
    private function userAgent(): string
    {
        $contact = config('services.nominatim.contact');

        return app_name().' CMS'.(is_string($contact) && $contact !== '' ? ' ('.$contact.')' : '');
    }
}
