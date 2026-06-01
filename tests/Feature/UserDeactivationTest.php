<?php

use App\Models\Group;
use App\Models\User;
use App\Services\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Reset test client state between tests to avoid session cookie leakage
    $this->defaultCookies = [];
    $this->serverVariables = [];
});

test('inactive user cannot log in', function () {
    $user = User::factory()->create([
        'password' => bcrypt('password'),
        'is_active' => false,
    ]);

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('active user can log in', function () {
    $user = User::factory()->create([
        'password' => bcrypt('password'),
        'is_active' => true,
    ]);

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);
});

test('group admin can deactivate a user', function () {
    $admin = User::factory()->create();
    $group = Group::factory()->create();
    $group->users()->attach($admin, ['is_admin' => true]);
    app(SessionService::class)->actAsGroup($group, true);
    $this->actingAs($admin);

    $user = User::factory()->create(['is_active' => true]);
    $group->users()->attach($user, ['is_admin' => false]);

    $this->put(route('users.update', $user), ['is_active' => false]);

    $this->assertFalse($user->fresh()->is_active);
});

test('group admin can reactivate a user', function () {
    $admin = User::factory()->create();
    $group = Group::factory()->create();
    $group->users()->attach($admin, ['is_admin' => true]);
    app(SessionService::class)->actAsGroup($group, true);
    $this->actingAs($admin);

    $user = User::factory()->create(['is_active' => false]);
    $group->users()->attach($user, ['is_admin' => false]);

    $this->put(route('users.update', $user), ['is_active' => true]);

    $this->assertTrue($user->fresh()->is_active);
});
