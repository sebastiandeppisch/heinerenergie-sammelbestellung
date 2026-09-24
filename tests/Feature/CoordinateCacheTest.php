<?php

declare(strict_types=1);

use App\Actions\FetchCoordinateByAddress;
use App\Actions\FetchCoordinateByFreeText;
use App\ValueObjects\Address;
use App\ValueObjects\Coordinate;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use maxh\Nominatim\Nominatim;
use maxh\Nominatim\Search;

afterEach(function (): void {
    // Only the throwaway stores this file created. Deleting the whole testing
    // directory would take the tracked .gitignore with it.
    foreach (File::glob(storage_path('framework/testing/cache-*')) as $directory) {
        File::deleteDirectory($directory);
    }
});

it('reads a cached Coordinate back as a Coordinate', function (): void {
    useFileCacheStore();

    Cache::forever('test.coordinate', new Coordinate(49.123, 8.321));

    expect(Cache::get('test.coordinate'))->toBeInstanceOf(Coordinate::class);
});

it('builds a Coordinate from the string values Nominatim returns', function (): void {
    $coordinate = Coordinate::fromArray(['lat' => '49.123', 'lon' => '8.321']);

    expect($coordinate->lat)->toBe(49.123)
        ->and($coordinate->lng)->toBe(8.321);
});

it('returns a Coordinate on a cache hit', function (): void {
    useFileCacheStore();

    $this->mock(Nominatim::class, function ($mock): void {
        $mock->shouldReceive('newSearch')->andReturn(new Search);
        $mock->shouldReceive('find')->andReturn([
            ['lat' => '49.123', 'lon' => '8.321'],
        ]);
    });

    $address = new Address('Luisenplatz', '1', '64283', 'Darmstadt');

    /**
     * AppServiceProvider swaps this action for a stub in the testing environment,
     * so the real implementation has to be built by hand here.
     */
    $fresh = (new FetchCoordinateByAddress)($address);
    $cached = (new FetchCoordinateByAddress)($address);

    expect($fresh)->toBeInstanceOf(Coordinate::class)
        ->and($cached)->toBeInstanceOf(Coordinate::class)
        ->and($cached->lat)->toBe($fresh->lat);
});

it('remembers that an address is unknown instead of asking again', function (): void {
    useFileCacheStore();

    $this->mock(Nominatim::class, function ($mock): void {
        $mock->shouldReceive('newSearch')->andReturn(new Search);
        // Once, not twice: a miss has to come out of the cache the second time.
        $mock->shouldReceive('find')->once()->andReturn([]);
    });

    $address = new Address('Nirgendwo', '99', '00000', 'Niemandsland');

    expect((new FetchCoordinateByAddress)($address))->toBeNull()
        ->and((new FetchCoordinateByAddress)($address))->toBeNull();
});

it('keeps address and free text lookups in separate cache entries', function (): void {
    useFileCacheStore();

    $this->mock(Nominatim::class, function ($mock): void {
        $mock->shouldReceive('newSearch')->andReturn(new Search);
        $mock->shouldReceive('find')->andReturn(
            [['lat' => '1.0', 'lon' => '2.0']],
            [['lat' => '3.0', 'lon' => '4.0']],
        );
    });

    // Both hash the very same string: an address carrying nothing but a postal
    // code, and the free text search for that postal code.
    $byAddress = (new FetchCoordinateByAddress)(new Address('', '', '64283', ''));
    $byText = (new FetchCoordinateByFreeText)('64283');

    expect($byAddress->lat)->toBe(1.0)
        ->and($byText->lat)->toBe(3.0);
});
