<?php

namespace Tests\Feature\ConsultingArea;

use App\Models\Group;
use App\Models\User;
use App\Services\SessionService;
use App\ValueObjects\Polygon;
use Config;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->group = Group::factory()->create();
    $this->admin = User::factory()->create();
    $this->group->users()->attach($this->admin, ['is_admin' => true]);

    app(SessionService::class)->actWithoutSelectingGroup();
    Config::set('app.group_context', 'global');
});

test('unauthorized users cannot update consulting area', function () {
    $user = User::factory()->create();
    $group = Group::factory()->create();

    actingAs($user)
        ->post(route('groups.consulting-area.update', $group), [
            'polygon' => [[49.8807, 8.6572], [49.8787, 8.6661]],
        ])
        ->assertForbidden();

    expect($group->fresh()->consulting_area)->toBeNull();
});

test('group admin can update consulting area', function () {
    $coordinates = [
        ["lat" => 49.8807, "lng" => 8.6572],
        ["lat" => 49.8787, "lng" => 8.6661],
        ["lat" => 49.8746, "lng" => 8.6630],
        ["lat" => 49.8731, "lng" => 8.6578],
        ["lat" => 49.8754, "lng" => 8.6525],
        ["lat" => 49.8782, "lng" => 8.6497],
        ["lat" => 49.8803, "lng" => 8.6509],
    ];

    actingAs($this->admin)
        ->post(route('groups.consulting-area.update', $this->group), [
            'polygon' => $coordinates,
        ])
        ->assertRedirect()
        ->assertSessionHas('success', 'Beratungsgebiet wurde erfolgreich gespeichert.');

    $this->group->refresh();

    expect($this->group->consulting_area)
        ->toBeInstanceOf(Polygon::class)
        ->and($this->group->consulting_area->getCoordinates())
        ->toMatchArray($coordinates);
});

test('it validates polygon format', function () {
    // Test invalid coordinate format
    actingAs($this->admin)
        ->post(route('groups.consulting-area.update', $this->group), [
            'polygon' => [[1]], // Invalid coordinate pair
        ])
        ->assertSessionHasErrors('polygon.*');

    // Test invalid coordinate values
    actingAs($this->admin)
        ->post(route('groups.consulting-area.update', $this->group), [
            'polygon' => [[181, 200]], // Invalid lat/long values
        ])
        ->assertSessionHasErrors('polygon.*.*');
});

test('consulting area can be cleared', function () {
    // First set a polygon
    $this->group->consulting_area = new Polygon([[49.8807, 8.6572], [49.8787, 8.6661]]);
    $this->group->save();

    actingAs($this->admin)
        ->delete(route('groups.consulting-area.delete', $this->group))
        ->assertRedirect();

    $this->group->refresh();
    expect($this->group->consulting_area)->toBeNull();
});
