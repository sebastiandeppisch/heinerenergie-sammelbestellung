<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->group = Group::factory()->create();
});

test('user with no group is redirected to group selection', function () {
    actingAs($this->user)
        ->get('/backend')
        ->assertRedirect('/initiatives/select');
});

test('user with single group gets it auto-selected', function () {
    // Add user to single group
    $this->group->users()->attach($this->user);

    $response = actingAs($this->user)
        ->get('/backend');

    // Should redirect to dashboard with group selected
    $response->assertRedirect('/dashboard');
    expect(session('actAsGroupId'))->toBe($this->group->id);
});

test('user with multiple groups is redirected to selection', function () {
    // Create and attach multiple groups
    $this->group->users()->attach($this->user);
    $secondGroup = Group::factory()->create();
    $secondGroup->users()->attach($this->user);

    actingAs($this->user)
        ->get('/backend')
        ->assertRedirect('/initiatives/select');
});

test('user with already selected group is redirected to dashboard', function () {
    $this->group->users()->attach($this->user);

    // First set the group
    actingAs($this->user)
        ->post("/actAsGroup/{$this->group->uuid}");

    // Then access backend
    $response = actingAs($this->user)
        ->get('/backend');

    $response->assertRedirect('/dashboard');
});
