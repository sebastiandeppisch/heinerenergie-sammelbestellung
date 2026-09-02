<?php

declare(strict_types=1);

use App\Actions\BuildPolygonFromPostalCodes;
use App\Actions\FetchPolygonsByPostalCode;
use App\Exceptions\PostalCodeAreaException;
use App\ValueObjects\Coordinate;
use maxh\Nominatim\Nominatim;
use maxh\Nominatim\Search;

/**
 * Answers with the recorded OpenStreetMap response of the requested postal code.
 */
function fakeNominatim(): void
{
    $nominatim = Mockery::mock(Nominatim::class);
    $nominatim->shouldReceive('newSearch')->andReturnUsing(fn (): Search => new Search);
    $nominatim->shouldReceive('find')->andReturnUsing(function (Search $search): array {
        $postalCode = $search->getQuery()['postalcode'];
        $fixture = __DIR__.'/../../Fixtures/PostalCodes/'.$postalCode.'.json';

        return file_exists($fixture) ? json_decode(file_get_contents($fixture), true) : [];
    });

    app()->instance(Nominatim::class, $nominatim);
}

beforeEach(function (): void {
    fakeNominatim();
    Cache::flush();
});

it('reads the boundary of a postal code area', function (): void {
    $polygons = app(FetchPolygonsByPostalCode::class)('64283');

    expect($polygons)->toHaveCount(1);

    $coordinates = $polygons[0]->getCoordinates();

    expect($coordinates)->toHaveCount(165)
        // Darmstadt city center
        ->and($coordinates[0]->lat)->toBeGreaterThan(49.0)->toBeLessThan(50.0)
        ->and($coordinates[0]->lng)->toBeGreaterThan(8.0)->toBeLessThan(9.0);
});

it('fails for an unknown postal code', function (): void {
    app(FetchPolygonsByPostalCode::class)('99999');
})->throws(PostalCodeAreaException::class, '99999');

it('caches the boundary of a postal code area', function (): void {
    app(FetchPolygonsByPostalCode::class)('64283');

    expect(Cache::has('postal-code-polygons.64283'))->toBeTrue();
});

it('builds one area out of adjacent postal codes', function (): void {
    $area = app(BuildPolygonFromPostalCodes::class)(['64283', '64285', '64287']);

    // The inner borders are gone, so the outline is shorter than the sum of all
    // three boundaries.
    expect($area->getCoordinates())->toHaveCount(666);

    // Luisenplatz, right in the middle of 64283
    expect($area->containsPoint(new Coordinate(49.8722, 8.6510)))->toBeTrue();
    // Frankfurt, far outside
    expect($area->containsPoint(new Coordinate(50.1109, 8.6821)))->toBeFalse();
});

it('builds an area out of a single postal code', function (): void {
    $area = app(BuildPolygonFromPostalCodes::class)(['64283']);

    expect($area->getCoordinates())->toHaveCount(164)
        ->and($area->containsPoint(new Coordinate(49.8722, 8.6510)))->toBeTrue();
});

it('ignores duplicated postal codes', function (): void {
    $area = app(BuildPolygonFromPostalCodes::class)(['64283', '64283', ' 64283 ']);

    expect($area->getCoordinates())->toHaveCount(164);
});

it('fails for postal codes that are not adjacent', function (): void {
    app(BuildPolygonFromPostalCodes::class)(['64283', '10115']);
})->throws(PostalCodeAreaException::class, 'zusammenhängendes Gebiet');
