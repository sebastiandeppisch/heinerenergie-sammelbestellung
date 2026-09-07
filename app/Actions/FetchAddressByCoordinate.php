<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\NominatimUnavailableException;
use App\Services\GeocodeCache;
use App\ValueObjects\Coordinate;
use Illuminate\Support\Facades\Log;
use maxh\Nominatim\Nominatim;
use Throwable;

class FetchAddressByCoordinate
{
    private readonly Nominatim $nominatim;

    private Coordinate $coordinate;

    public function __construct(

    ) {
        $this->nominatim = app(Nominatim::class);
    }

    /**
     * Returns null when OpenStreetMap knows no address at the coordinate.
     *
     * @throws NominatimUnavailableException if OpenStreetMap could not be reached
     */
    public function __invoke(Coordinate $coordinate): ?string
    {
        Log::debug('Fetching address for coordinate', ['coordinate' => $coordinate]);
        $this->coordinate = $coordinate;

        return app(GeocodeCache::class)->remember($this->key(), fn (): ?string => $this->handle());
    }

    private function key(): string
    {
        return 'address.'.$this->coordinate->lat.','.$this->coordinate->lng;
    }

    private function handle(): ?string
    {
        $coordinate = $this->coordinate;
        $reverse = $this->nominatim->newReverse()->latlon($coordinate->lat, $coordinate->lng);

        try {
            $result = $this->nominatim->find($reverse);
        } catch (Throwable $e) {
            Log::error('Nominatim reverse geocoding failed', ['coordinate' => $coordinate, 'exception' => $e]);

            throw NominatimUnavailableException::requestFailed($e);
        }

        Log::debug('Nominatim reverse geocoding result', ['result' => $result, 'coordinate' => $coordinate]);

        return $result['display_name'] ?? null;
    }
}
