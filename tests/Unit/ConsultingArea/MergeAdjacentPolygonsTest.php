<?php

declare(strict_types=1);

use App\Actions\MergeAdjacentPolygons;
use App\Exceptions\PostalCodeAreaException;
use App\ValueObjects\Coordinate;
use App\ValueObjects\Polygon;

/**
 * Builds an axis aligned rectangle with a vertex on every grid step, so that
 * neighbouring rectangles share their border vertex by vertex, just like the
 * postal code areas coming from OpenStreetMap do.
 *
 * @param  float  $step  distance between two vertices
 */
function rectangle(float $left, float $bottom, float $right, float $top, float $step = 1.0): Polygon
{
    $coordinates = [];

    for ($lng = $left; $lng < $right; $lng += $step) {
        $coordinates[] = new Coordinate($bottom, $lng);
    }
    for ($lat = $bottom; $lat < $top; $lat += $step) {
        $coordinates[] = new Coordinate($lat, $right);
    }
    for ($lng = $right; $lng > $left; $lng -= $step) {
        $coordinates[] = new Coordinate($top, $lng);
    }
    for ($lat = $top; $lat > $bottom; $lat -= $step) {
        $coordinates[] = new Coordinate($lat, $left);
    }

    return new Polygon($coordinates);
}

/**
 * @param  array<int, array{float, float}>  $expected  [lat, lng] pairs
 */
function expectRing(Polygon $polygon, array $expected): void
{
    $actual = array_map(fn (Coordinate $c): array => [$c->lat, $c->lng], $polygon->getCoordinates());

    expect($actual)->toHaveCount(count($expected));

    // The ring may start at any vertex and run in either direction.
    $rotations = [];
    foreach ([$actual, array_reverse($actual)] as $candidate) {
        for ($offset = 0; $offset < count($candidate); $offset++) {
            $rotations[] = array_merge(array_slice($candidate, $offset), array_slice($candidate, 0, $offset));
        }
    }

    expect($rotations)->toContain($expected);
}

it('returns the polygon itself when only one is given', function (): void {
    $polygon = rectangle(0, 0, 2, 2);

    $merged = app(MergeAdjacentPolygons::class)([$polygon]);

    expectRing($merged, [[0.0, 0.0], [0.0, 1.0], [0.0, 2.0], [1.0, 2.0], [2.0, 2.0], [2.0, 1.0], [2.0, 0.0], [1.0, 0.0]]);
});

it('drops the closing vertex of a ring', function (): void {
    $polygon = new Polygon([
        ['lat' => 0, 'lng' => 0],
        ['lat' => 0, 'lng' => 1],
        ['lat' => 1, 'lng' => 1],
        ['lat' => 0, 'lng' => 0],
    ]);

    $merged = app(MergeAdjacentPolygons::class)([$polygon]);

    expect($merged->getCoordinates())->toHaveCount(3);
});

it('merges two adjacent polygons into one outline', function (): void {
    $left = rectangle(0, 0, 1, 1);
    $right = rectangle(1, 0, 2, 1);

    $merged = app(MergeAdjacentPolygons::class)([$left, $right]);

    // The shared border at lng = 1 is gone, the result is one big rectangle.
    expectRing($merged, [[0.0, 0.0], [0.0, 1.0], [0.0, 2.0], [1.0, 2.0], [1.0, 1.0], [1.0, 0.0]]);
});

it('merges three adjacent polygons into one outline', function (): void {
    $merged = app(MergeAdjacentPolygons::class)([
        rectangle(0, 0, 1, 1),
        rectangle(1, 0, 2, 1),
        rectangle(2, 0, 3, 1),
    ]);

    expectRing($merged, [[0.0, 0.0], [0.0, 1.0], [0.0, 2.0], [0.0, 3.0], [1.0, 3.0], [1.0, 2.0], [1.0, 1.0], [1.0, 0.0]]);
});

it('merges polygons that touch along a part of their border only', function (): void {
    $merged = app(MergeAdjacentPolygons::class)([
        rectangle(0, 0, 1, 2),
        rectangle(1, 0, 2, 1),
    ]);

    expect($merged->containsPoint(new Coordinate(1.5, 0.5)))->toBeTrue()
        ->and($merged->containsPoint(new Coordinate(0.5, 1.5)))->toBeTrue()
        ->and($merged->containsPoint(new Coordinate(1.5, 1.5)))->toBeFalse();
});

it('keeps the merged area usable for the point in polygon check', function (): void {
    $merged = app(MergeAdjacentPolygons::class)([
        rectangle(0, 0, 1, 1),
        rectangle(1, 0, 2, 1),
    ]);

    expect($merged->containsPoint(new Coordinate(0.5, 0.5)))->toBeTrue()
        ->and($merged->containsPoint(new Coordinate(0.5, 1.5)))->toBeTrue()
        ->and($merged->containsPoint(new Coordinate(0.5, 2.5)))->toBeFalse();
});

it('fails when the polygons do not touch each other', function (): void {
    app(MergeAdjacentPolygons::class)([
        rectangle(0, 0, 1, 1),
        rectangle(5, 0, 6, 1),
    ]);
})->throws(PostalCodeAreaException::class, 'zusammenhängendes Gebiet');

it('fails when the polygons touch in a single point only', function (): void {
    app(MergeAdjacentPolygons::class)([
        rectangle(0, 0, 1, 1),
        rectangle(1, 1, 2, 2),
    ]);
})->throws(PostalCodeAreaException::class, 'zusammenhängendes Gebiet');

it('fails when the same polygon is merged with itself', function (): void {
    $polygon = rectangle(0, 0, 1, 1);

    app(MergeAdjacentPolygons::class)([$polygon, $polygon]);
})->throws(PostalCodeAreaException::class, 'zusammenhängendes Gebiet');
