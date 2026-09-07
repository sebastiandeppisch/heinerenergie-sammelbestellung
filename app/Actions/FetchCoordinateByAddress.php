<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\NominatimUnavailableException;
use App\Services\GeocodeCache;
use App\ValueObjects\Address;
use App\ValueObjects\Coordinate;
use Illuminate\Support\Facades\Log;
use maxh\Nominatim\Nominatim;
use Throwable;

class FetchCoordinateByAddress
{
    private readonly Nominatim $nominatim;

    private Address $address;

    public function __construct(

    ) {
        $this->nominatim = app(Nominatim::class);
    }

    /**
     * Returns null when OpenStreetMap does not know the address.
     *
     * @throws NominatimUnavailableException if OpenStreetMap could not be reached
     */
    public function __invoke(Address $address): ?Coordinate
    {
        Log::debug('Fetching coordinates for address', ['address' => $address]);
        $this->address = $address;

        return app(GeocodeCache::class)->remember($this->key(), fn (): ?Coordinate => $this->handle());
    }

    private function key(): string
    {
        return 'coordinates.address.'.$this->address->hash();
    }

    private function handle(): ?Coordinate
    {
        $address = $this->address;
        $search = $this->nominatim->newSearch()
            ->country('Deutschland')
            ->postalCode($address->zip)
            ->street($address->streetWithNumber())
            ->city($address->city);

        try {
            $result = $this->nominatim->find($search);
        } catch (Throwable $e) {
            Log::error('Nominatim search failed', ['address' => $address, 'exception' => $e]);

            throw NominatimUnavailableException::requestFailed($e);
        }

        Log::debug('Nominatim search result', ['result' => $result, 'address' => $address]);

        if (count($result) > 0) {
            return Coordinate::fromArray($result[0]);
        }

        return null;
    }
}
