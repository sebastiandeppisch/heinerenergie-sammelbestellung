<?php

use App\Actions\FetchCoordinateByAddress;
use App\Actions\FetchCoordinateByFreeText;
use App\Jobs\AssignAdviceToGroupByAddress;
use App\Jobs\AssignAdviceToGroupByZipcode;
use App\Models\Advice;
use App\Models\Group;
use App\Models\User;
use App\Notifications\NewAdviceNearby;
use App\Notifications\SystemErrorNotification;
use App\ValueObjects\Address;
use App\ValueObjects\Coordinate;
use App\ValueObjects\Polygon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

// Test for assigning an advice to an initiative based on coordinates
test('advice is assigned to correct group based on coordinates', function () {
    // Create two groups with defined areas (polygons)
    $correctGroup = Group::factory()->create([
        'name' => 'Correct Group',
        'consulting_area' => new Polygon([
            ['lat' => 48.0, 'lng' => 8.0],
            ['lat' => 48.0, 'lng' => 9.0],
            ['lat' => 49.0, 'lng' => 9.0],
            ['lat' => 49.0, 'lng' => 8.0],
            ['lat' => 48.0, 'lng' => 8.0], // Close polygon
        ]),
    ]);

    $otherGroup = Group::factory()->create([
        'name' => 'Other Group',
        'consulting_area' => new Polygon([
            ['lat' => 49.1, 'lng' => 8.0],
            ['lat' => 49.1, 'lng' => 9.0],
            ['lat' => 50.0, 'lng' => 9.0],
            ['lat' => 50.0, 'lng' => 8.0],
            ['lat' => 49.1, 'lng' => 8.0], // Close polygon
        ]),
    ]);

    // Register the FetchCoordinateByAddress action in the container
    App::bind(FetchCoordinateByAddress::class, fn () => function (Address $address) {
        return new Coordinate(48.5, 8.5); // Inside the correct group's area
    });

    // Also bind FetchCoordinateByFreeText to avoid issues if it's called
    App::bind(FetchCoordinateByFreeText::class, fn () => fn (string $text) => new Coordinate(48.5, 8.5));

    // Create a new advice
    $advice = Advice::factory()->create([
        'street' => 'Teststraße',
        'street_number' => '123',
        'zip' => '12345',
        'city' => 'Teststadt',
    ]);

    // Run the job
    new AssignAdviceToGroupByAddress($advice)->handle();

    // Refresh advice from database
    $advice->refresh();

    // Check if the advice was assigned to the correct group
    expect($advice->group_id)->toBe($correctGroup->id);
    expect($advice->group_id)->not->toBe($otherGroup->id);
});

// Test for system administrator notification on geocoding failure
test('system administrators are notified on geocoding failure', function () {
    // Create system administrator
    $admin = User::factory()->create(['is_admin' => true]);

    // Register FetchCoordinateByAddress to return null (geocoding failure)
    App::bind(FetchCoordinateByAddress::class, fn () => fn (Address $address) => null);

    // Register FetchCoordinateByFreeText to return null (zipcode geocoding failure)
    App::bind(FetchCoordinateByFreeText::class, fn () => fn (string $text) => null);

    // Create advice
    $advice = Advice::factory()->create([
        'zip' => '12345',
    ]);

    // Catch notifications
    Notification::fake();

    // Run jobs
    new AssignAdviceToGroupByAddress($advice)->handle();

    // Verify that system administrators were notified
    Notification::assertSentTo(
        $admin,
        SystemErrorNotification::class,
        fn ($notification) => $notification->advice->id === $advice->id
    );

    // This line verifies the job completes without exceptions
    expect(true)->toBeTrue();
});

// Test for assigning advice to main group even when a closer subgroup exists
test('advice is assigned to main group even when subgroup is closer', function () {
    // Create a main group (no parent) with consulting area far from coordinates
    $mainGroup = Group::factory()->create([
        'name' => 'Main Group',
        'parent_id' => null, // Main group
        'consulting_area' => new Polygon([
            ['lng' => 10.0, 'lat' => 10.0],
            ['lng' => 10.0, 'lat' => 11.0],
            ['lng' => 11.0, 'lat' => 11.0],
            ['lng' => 11.0, 'lat' => 10.0],
            ['lng' => 10.0, 'lat' => 10.0], // Close polygon
        ]),
    ]);

    // Create a subgroup (with parent) with consulting area closer to coordinates
    $subGroup = Group::factory()->create([
        'name' => 'Sub Group',
        'parent_id' => $mainGroup->id,
        'consulting_area' => new Polygon([
            ['lng' => 1.0, 'lat' => 1.0],
            ['lng' => 1.0, 'lat' => 2.0],
            ['lng' => 2.0, 'lat' => 2.0],
            ['lng' => 2.0, 'lat' => 1.0],
            ['lng' => 1.0, 'lat' => 1.0], // Close polygon
        ]),
    ]);

    // Register FetchCoordinateByAddress to return null to force zipcode-based assignment
    App::bind(FetchCoordinateByAddress::class, fn () => function (Address $address) {
        return null; // Force zipcode-based assignment
    });

    // Register FetchCoordinateByFreeText to return coordinates
    App::bind(FetchCoordinateByFreeText::class, fn () => fn (string $text) =>
        // Return a coordinate that would be closer to the subgroup's polygon
        new Coordinate(1.5, 1.5));

    // Create a system admin to receive notifications
    $admin = User::factory()->create(['is_admin' => true]);

    // Create a new advice
    $advice = Advice::factory()->create([
        'zip' => '12345',
    ]);

    // Catch notifications
    Notification::fake();

    // Run jobs - first the address job will fail with null coordinates
    new AssignAdviceToGroupByAddress($advice)->handle();

    // Then we'll run the zipcode job directly (as it would be triggered in the real flow)
    new AssignAdviceToGroupByZipcode($advice)->handle();

    // Refresh advice from database
    $advice->refresh();

    // Verify that advice was assigned to the main group, not the subgroup
    expect($advice->group_id)->toBe($mainGroup->id);
    expect($advice->group_id)->not->toBe($subGroup->id);
});

// Test for assigning advice to subgroup when main group already exists
test('advice is assigned to subgroup when main group already exists and address is found', function () {
    // Create a main group first
    $mainGroup = Group::factory()->create([
        'name' => 'Main Group',
        'parent_id' => null, // Main group
        'consulting_area' => new Polygon([
            ['lat' => 48.0, 'lng' => 8.0],
            ['lat' => 48.0, 'lng' => 9.0],
            ['lat' => 49.0, 'lng' => 9.0],
            ['lat' => 49.0, 'lng' => 8.0],
            ['lat' => 48.0, 'lng' => 8.0], // Close polygon
        ]),
    ]);

    // Create a subgroup within the main group's area
    $subGroup = Group::factory()->create([
        'name' => 'Sub Group',
        'parent_id' => $mainGroup->id,
        'consulting_area' => new Polygon([
            ['lat' => 48.2, 'lng' => 8.2],
            ['lat' => 48.2, 'lng' => 8.8],
            ['lat' => 48.8, 'lng' => 8.8],
            ['lat' => 48.8, 'lng' => 8.2],
            ['lat' => 48.2, 'lng' => 8.2], // Close polygon
        ]),
    ]);

    // Create group admins to receive notifications
    $mainGroupAdvisor = User::factory()->create([
        'coordinate' => $mainGroup->consulting_area->getCenter(),
    ]);
    $subGroupAdvisor = User::factory()->create([
        'coordinate' => $subGroup->consulting_area->getCenter(),
    ]);
    $mainGroup->users()->attach($mainGroupAdvisor);
    $subGroup->users()->attach($subGroupAdvisor);

    // Register the FetchCoordinateByAddress action to return coordinates inside the subgroup

    $adviceCoordinates = $subGroup->consulting_area->getCenter();

    App::bind(FetchCoordinateByAddress::class, fn () => function (Address $address) use ($adviceCoordinates) {
        return $adviceCoordinates;
    });

    // Create a new advice with an existing group_id (main group already set)
    $advice = Advice::factory()->create([
        'street' => 'Teststraße',
        'street_number' => '123',
        'zip' => '12345',
        'city' => 'Teststadt',
        'group_id' => $mainGroup->id, // Main group already exists
    ]);

    // Catch notifications
    Notification::fake();

    // Run the job
    new AssignAdviceToGroupByAddress($advice)->handle();

    // Refresh advice from database
    $advice = $advice->refresh();

    // Check if the advice was assigned to the subgroup (not the main group anymore)
    expect($advice->group_id)->toBe($subGroup->id);
    expect($advice->group_id)->not->toBe($mainGroup->id);

    // Verify that subgroup advisor was notified
    Notification::assertSentTo(
        $subGroupAdvisor,
        NewAdviceNearby::class
    );

})->only();
