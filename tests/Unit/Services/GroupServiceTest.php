<?php

namespace Tests\Unit\Services;

use App\Models\Advice;
use App\Models\Group;
use App\Services\GroupService;
use App\ValueObjects\Coordinate;
use App\ValueObjects\Meter;
use App\ValueObjects\Polygon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->groupService = new GroupService;
});

test('find group containing coordinates', function () {
    // Create a group with a polygonal consulting area
    $group = Group::factory()->create([
        'consulting_area' => new Polygon([
            [8.0, 48.0],  // [long, lat]
            [9.0, 48.0],
            [9.0, 49.0],
            [8.0, 49.0],
            [8.0, 48.0],  // Close the polygon
        ]),
    ]);

    // Check if a coordinate inside the polygon finds the group
    $coordinate = new Coordinate(lat: 48.5, lng: 8.5);  // Inside the polygon
    $foundGroup = $this->groupService->findGroupContainingCoordinates($coordinate);
    expect($foundGroup)->not->toBeNull();
    expect($foundGroup->id)->toBe($group->id);

    // Check if a coordinate outside the polygon returns no group
    $outsideCoordinate = new Coordinate(lat: 47.0, lng: 7.0);  // Outside the polygon
    $notFoundGroup = $this->groupService->findGroupContainingCoordinates($outsideCoordinate);
    expect($notFoundGroup)->toBeNull();
});

test('find nearest main group', function () {
    // Create a main group (parent_id = null)
    $mainGroup1 = Group::factory()->create([
        'name' => 'Nearby Group',
        'parent_id' => null,
        'consulting_area' => new Polygon([
            [8.0, 48.0],  // [long, lat]
            [9.0, 48.0],
            [9.0, 49.0],
            [8.0, 49.0],
            [8.0, 48.0],  // Close the polygon
        ]),
    ]);

    // Create a second main group that is further away
    $mainGroup2 = Group::factory()->create([
        'name' => 'Distant Group',
        'parent_id' => null,
        'consulting_area' => new Polygon([
            [20.0, 60.0],  // Much further away
            [21.0, 60.0],
            [21.0, 61.0],
            [20.0, 61.0],
            [20.0, 60.0],  // Close the polygon
        ]),
    ]);

    // create a sub group that is closer
    $subGroup = Group::factory()->create([
        'name' => 'Sub Group',
        'parent_id' => $mainGroup1->id,
        'consulting_area' => new Polygon([
            [8.5, 48.0],  // [long, lat]
            [8.0, 49.0],
            [8.0, 48.0],
        ]),
    ]);

    // Check if a nearby coordinate finds the nearest group
    $nearbyCoordinate = new Coordinate(lat: 8.5, lng: 48.5);  // Near the first group
    $foundNearGroup = $this->groupService->findNearestMainGroup($nearbyCoordinate);
    expect($foundNearGroup)->not->toBeNull();
    expect($foundNearGroup->name)->toBe('Nearby Group');

    // Check if a distant coordinate finds the other group
    $distantCoordinate = new Coordinate(lat: 20.5, lng: 60.5);  // Near the second group
    $foundDistantGroup = $this->groupService->findNearestMainGroup($distantCoordinate);
    expect($foundDistantGroup)->not->toBeNull();
    expect($foundDistantGroup->name)->toBe('Distant Group');
});

test('assign advice to group', function () {
    // Create a group and an advice
    $group = Group::factory()->create();
    $advice = Advice::factory()->create();

    // Check if the advice is correctly assigned to the group
    $this->groupService->assignAdviceToGroup($advice, $group);
    $advice->refresh(); // Refresh from database

    expect($advice->group_id)->toBe($group->id);
});

test('identify main group correctly', function () {
    // Create a main group and a subgroup
    $mainGroup = Group::factory()->create(['parent_id' => null]);
    $subGroup = Group::factory()->create(['parent_id' => $mainGroup->id]);

    // Check if the isMainGroup method works correctly
    expect($this->groupService->isMainGroup($mainGroup))->toBeTrue();
    expect($this->groupService->isMainGroup($subGroup))->toBeFalse();
});

test('get all main groups', function () {
    // Create multiple main groups and subgroups
    $mainGroup1 = Group::factory()->create(['parent_id' => null]);
    $mainGroup2 = Group::factory()->create(['parent_id' => null]);
    Group::factory()->create(['parent_id' => $mainGroup1->id]);
    Group::factory()->create(['parent_id' => $mainGroup2->id]);

    // Check if main groups are returned and include our test groups
    $mainGroups = $this->groupService->getAllMainGroups();
    expect($mainGroups->count())->toBeGreaterThanOrEqual(2);
    expect($mainGroups->contains('id', $mainGroup1->id))->toBeTrue();
    expect($mainGroups->contains('id', $mainGroup2->id))->toBeTrue();
});

test('get subgroups of a main group', function () {
    // Create a main group with multiple subgroups
    $mainGroup = Group::factory()->create(['parent_id' => null]);
    $subGroup1 = Group::factory()->create(['parent_id' => $mainGroup->id]);
    $subGroup2 = Group::factory()->create(['parent_id' => $mainGroup->id]);

    // Create another main group that shouldn't be included
    $otherMainGroup = Group::factory()->create(['parent_id' => null]);

    // Check if only the subgroups of the main group are returned
    $subgroups = $this->groupService->getSubgroups($mainGroup);
    expect($subgroups)->toHaveCount(2);
    expect($subgroups->contains('id', $subGroup1->id))->toBeTrue();
    expect($subgroups->contains('id', $subGroup2->id))->toBeTrue();

    // Check if no subgroups are returned for a subgroup
    $emptySubgroups = $this->groupService->getSubgroups($subGroup1);
    expect($emptySubgroups)->toHaveCount(0);
});

test('calculate distance between groups', function () {
    // Create two groups with polygons
    $group1 = Group::factory()->create([
        'consulting_area' => new Polygon([
            [8.0, 48.0],  // [long, lat]
            [9.0, 48.0],
            [9.0, 49.0],
            [8.0, 49.0],
            [8.0, 48.0],  // Close the polygon
        ]),
    ]);

    $group2 = Group::factory()->create([
        'consulting_area' => new Polygon([
            [20.0, 60.0],  // Much further away
            [21.0, 60.0],
            [21.0, 61.0],
            [20.0, 61.0],
            [20.0, 60.0],  // Close the polygon
        ]),
    ]);

    // Calculate the distance between the groups
    $distance = $this->groupService->getDistanceBetweenGroups($group1, $group2);
    expect($distance)->not->toBeNull();

    // Check that a Meter object is returned
    expect($distance)->toBeInstanceOf(Meter::class);

    // Check that the value is in the expected range
    expect($distance->getValue())->toBeGreaterThan(1000000); // Should be more than 1000km

    // Group without polygon should return null
    $groupWithoutArea = Group::factory()->create();
    $nullDistance = $this->groupService->getDistanceBetweenGroups($group1, $groupWithoutArea);
    expect($nullDistance)->toBeNull();
});
