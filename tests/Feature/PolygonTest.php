<?php

use App\ValueObjects\Coordinate;
use App\ValueObjects\Polygon;

// Test for basic polygon containment
test('polygon can determine if point is inside', function () {
    // Create a square polygon
    $polygon = new Polygon([
        ['lat' => 0, 'lng' => 0],
        ['lat' => 0, 'lng' => 10],
        ['lat' => 10, 'lng' => 10],
        ['lat' => 10, 'lng' => 0],
        ['lat' => 0, 'lng' => 0], // Close the polygon
    ]);

    // Test points inside
    $inside = new Coordinate(5, 5);
    expect($polygon->containsPoint($inside))->toBeTrue();

    // Test points outside
    $outside = new Coordinate(15, 15);
    expect($polygon->containsPoint($outside))->toBeFalse();

    // Test boundary points
    $boundary = new Coordinate(0, 5);
    expect($polygon->containsPoint($boundary))->toBeTrue();
});

// Test with a simple triangle
test('polygon handles triangle correctly', function () {
    // Create a simple triangle
    $polygon = new Polygon([
        ['lat' => 0,  'lng' => 0],   // Bottom left
        ['lat' => 10, 'lng' => 0],  // Bottom right
        ['lat' => 5,  'lng' => 10],  // Top middle
        ['lat' => 0,  'lng' => 0],    // Close the polygon
    ]);

    // Inside point
    $inside = new Coordinate(5, 5);
    expect($polygon->containsPoint($inside))->toBeTrue();

    // Outside point
    $outside = new Coordinate(15, 15);
    expect($polygon->containsPoint($outside))->toBeFalse();
});

// Test for invalid polygons
test('polygon returns false for invalid polygons', function () {
    // Invalid polygon (less than 3 points)
    $invalidPolygon = new Polygon([
        ['lat' => 0,  'lng' => 0],
        ['lat' => 10, 'lng' => 10],
    ]);

    $point = new Coordinate(5, 5);
    expect($invalidPolygon->containsPoint($point))->toBeFalse();
});

// Test for an extreme example - concave polygon
test('polygon handles concave shapes correctly', function () {
    // Concave polygon (U-shape)
    $polygon = new Polygon([
        ['lat' => 0, 'lng' => 0],
        ['lat' => 0, 'lng' => 10],
        ['lat' => 3, 'lng' => 10],
        ['lat' => 3, 'lng' => 3],
        ['lat' => 7, 'lng' => 3],
        ['lat' => 7, 'lng' => 10],
        ['lat' => 10, 'lng' => 10],
        ['lat' => 10, 'lng' => 0],
        ['lat' => 0, 'lng' => 0], // Close the polygon
    ]);

    // Point in the concavity (should be outside)
    $concavity = new Coordinate(5, 5);
    expect($polygon->containsPoint($concavity))->toBeFalse();

    // Point in the solid part (should be inside)
    $solid = new Coordinate(2, 2);
    expect($polygon->containsPoint($solid))->toBeTrue();
});

test('test getCenter returns average of polygon coordinates', function () {
    $polygon = new Polygon([
        ['lat' => 0, 'lng' => 0],
        ['lat' => 4, 'lng' => 0],
        ['lat' => 4, 'lng' => 4],
        ['lat' => 0, 'lng' => 4],
    ]);

    $center = $polygon->getCenter();

    expect($center->lat)->toBe(2.0);
    expect($center->lng)->toBe(2.0);
});
