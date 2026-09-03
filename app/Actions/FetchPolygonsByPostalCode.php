<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\PostalCodeAreaException;
use App\ValueObjects\Polygon;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use maxh\Nominatim\Nominatim;

/**
 * Loads the boundary of a german postal code area from OpenStreetMap.
 *
 * A postal code area can consist of more than one polygon, therefore a list of
 * polygons is returned.
 */
class FetchPolygonsByPostalCode
{
    private readonly Nominatim $nominatim;

    public function __construct()
    {
        $this->nominatim = app(Nominatim::class);
    }

    /**
     * @return array<int, Polygon>
     *
     * @throws PostalCodeAreaException if the postal code is unknown to OpenStreetMap
     */
    public function __invoke(string $postalCode): array
    {
        Log::debug('Fetching polygons for postal code', ['postalCode' => $postalCode]);

        /** @var array<int, array<int, array{lat: float, lng: float}>> $rings */
        $rings = Cache::rememberForever($this->key($postalCode), fn (): array => $this->handle($postalCode));

        if ($rings === []) {
            throw PostalCodeAreaException::notFound($postalCode);
        }

        return array_map(fn (array $ring): Polygon => new Polygon($ring), $rings);
    }

    private function key(string $postalCode): string
    {
        return 'postal-code-polygons.'.$postalCode;
    }

    /**
     * The result is cached, so it is kept as plain arrays instead of value objects.
     *
     * @return array<int, array<int, array{lat: float, lng: float}>>
     */
    private function handle(string $postalCode): array
    {
        $search = $this->nominatim->newSearch()
            ->format('jsonv2')
            ->country('Deutschland')
            ->postalCode($postalCode)
            ->polygon('geojson');

        try {
            $results = $this->nominatim->find($search);
        } catch (ClientException $e) {
            Log::error($e->getResponse()->getBody()->getContents());
            throw $e;
        }

        foreach ($results as $result) {
            $rings = $this->ringsOf($result['geojson'] ?? null);

            if ($rings !== []) {
                return $rings;
            }
        }

        return [];
    }

    /**
     * Reads the outer rings of a GeoJSON geometry. Inner rings (holes) are not
     * supported by the consulting area and therefore ignored.
     *
     * @param  array<string, mixed>|null  $geoJson
     * @return array<int, array<int, array{lat: float, lng: float}>>
     */
    private function ringsOf(?array $geoJson): array
    {
        $coordinates = $geoJson['coordinates'] ?? null;

        if (! is_array($coordinates)) {
            return [];
        }

        $outerRings = match ($geoJson['type'] ?? null) {
            'Polygon' => [$coordinates[0] ?? []],
            'MultiPolygon' => array_map(fn (array $polygon): array => $polygon[0] ?? [], $coordinates),
            default => [],
        };

        $rings = [];

        foreach ($outerRings as $ring) {
            if (count($ring) < 3) {
                continue;
            }

            // GeoJSON uses [longitude, latitude]
            $rings[] = array_map(fn (array $point): array => [
                'lat' => (float) $point[1],
                'lng' => (float) $point[0],
            ], $ring);
        }

        return $rings;
    }
}
