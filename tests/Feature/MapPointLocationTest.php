<?php

use App\Actions\FetchAddressByCoordinate;
use App\Models\Group;
use App\Models\MapPoint;
use App\Models\User;
use App\Services\SessionService;
use App\ValueObjects\Coordinate;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);

    $this->group = Group::create([
        'name' => 'Test Group',
        'description' => 'Test Description',
    ]);

    app(SessionService::class)->actAsGroup($this->group);
    Config::set('app.group_context', 'global');
});

test('a map point can be created with a location', function () {
    $response = $this->actingAs($this->admin)
        ->post(route('mappoints.store'), [
            'title' => 'Test Point',
            'description' => 'Test description',
            'coordinate' => ['lat' => 52.5, 'lng' => 13.4],
            'published' => true,
            'category_id' => null,
            'location' => 'Musterstraße 1, 64283 Darmstadt',
        ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();

    $this->assertDatabaseHas('map_points', [
        'title' => 'Test Point',
        'location' => 'Musterstraße 1, 64283 Darmstadt',
    ]);
});

test('a map point location can be updated', function () {
    $mapPoint = MapPoint::factory()->create(['location' => 'Alte Adresse']);

    $response = $this->actingAs($this->admin)
        ->put(route('mappoints.update', $mapPoint), [
            'title' => $mapPoint->title,
            'description' => $mapPoint->description,
            'coordinate' => ['lat' => 52.5, 'lng' => 13.4],
            'published' => $mapPoint->published,
            'category_id' => null,
            'location' => 'Neue Adresse',
        ]);

    $response->assertRedirect();

    $mapPoint->refresh();
    expect($mapPoint->location)->toBe('Neue Adresse');
});

test('a map point location must not exceed the maximum length', function () {
    $response = $this->actingAs($this->admin)
        ->post(route('mappoints.store'), [
            'title' => 'Test Point',
            'coordinate' => ['lat' => 52.5, 'lng' => 13.4],
            'published' => true,
            'location' => str_repeat('a', 501),
        ]);

    $response->assertSessionHasErrors(['location']);
});

test('the reverse geocoding api endpoint returns a location for a coordinate', function () {
    $response = $this->actingAs($this->admin)
        ->getJson('/api/map/reverse-search?lat=49.8728&lng=8.6512');

    $response->assertStatus(200);
    $response->assertJson(['location' => app(FetchAddressByCoordinate::class)(new Coordinate(49.8728, 8.6512))]);
});

test('the reverse geocoding api endpoint requires authentication', function () {
    $response = $this->getJson('/api/map/reverse-search?lat=49.8728&lng=8.6512');

    $response->assertStatus(401);
});
