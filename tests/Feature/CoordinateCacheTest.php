<?php

declare(strict_types=1);

use App\Actions\FetchCoordinateByAddress;
use App\ValueObjects\Address;
use App\ValueObjects\Coordinate;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use maxh\Nominatim\Nominatim;
use maxh\Nominatim\Search;

afterEach(function (): void {
    File::deleteDirectory(storage_path('framework/testing'));
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
