<?php

use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->group = Group::factory()->create();
    $this->group->users()->attach($this->admin->id, ['is_admin' => true]);

    Config::set('app.group_context', 'global');
});

test('inactive user does not appear in api/users lookup', function () {
    $active = User::factory()->create(['is_active' => true]);
    $inactive = User::factory()->create(['is_active' => false]);
    $this->group->users()->attach([$active->id => ['is_admin' => false], $inactive->id => ['is_admin' => false]]);

    actingAs($this->admin)
        ->get('/api/users')
        ->assertOk()
        ->assertJsonFragment(['id' => $active->uuid])
        ->assertJsonMissing(['id' => $inactive->uuid]);
});

test('inactive user does not appear in api/users withoutself lookup', function () {
    $active = User::factory()->create(['is_active' => true]);
    $inactive = User::factory()->create(['is_active' => false]);
    $this->group->users()->attach([$active->id => ['is_admin' => false], $inactive->id => ['is_admin' => false]]);

    actingAs($this->admin)
        ->get('/api/users?withoutself=1')
        ->assertOk()
        ->assertJsonFragment(['id' => $active->uuid])
        ->assertJsonMissing(['id' => $inactive->uuid]);
});
