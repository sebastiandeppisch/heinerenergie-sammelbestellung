<?php

use App\ValueObjects\Coordinate;
use App\ValueObjects\Polygon;

// Test for basic polygon containment
test('polygon can determine if point is inside', function () {
    // Create a square polygon
    $polygon = new Polygon([
        [0, 0],
        [0, 10],
        [10, 10],
        [10, 0],
        [0, 0], // Close the polygon
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
        [0, 0],   // Bottom left
        [10, 0],  // Bottom right
        [5, 10],  // Top middle
        [0, 0],    // Close the polygon
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
        [0, 0],
        [10, 10],
    ]);

    $point = new Coordinate(5, 5);
    expect($invalidPolygon->containsPoint($point))->toBeFalse();
});

// Test for an extreme example - concave polygon
test('polygon handles concave shapes correctly', function () {
    // Concave polygon (U-shape)
    $polygon = new Polygon([
        [0, 0],
        [0, 10],
        [3, 10],
        [3, 3],
        [7, 3],
        [7, 10],
        [10, 10],
        [10, 0],
        [0, 0], // Close the polygon
    ]);

    // Point in the concavity (should be outside)
    $concavity = new Coordinate(5, 5);
    expect($polygon->containsPoint($concavity))->toBeFalse();

    // Point in the solid part (should be inside)
    $solid = new Coordinate(2, 2);
    expect($polygon->containsPoint($solid))->toBeTrue();
});
