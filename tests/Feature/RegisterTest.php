<?php

use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the first registered is logged in and can login again', function (): void {
    $this->post('/register', [
        'first_name' => 'Max',
        'last_name' => 'Mustermann',
        'email' => 'max.mustermann@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect('/dashboard');

    $this->assertAuthenticated();

    $this->post('/logout');

    $this->assertGuest();

    $response = $this->post('/api/login', [
        'email' => 'max.mustermann@example.com',
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
})->skip();

test('a user can login', function (): void {
    User::factory()->create([
        'email' => 'john.doe@example.com',
        'password' => 'password',
    ]);

    $this->post('/api/login', [
        'email' => 'john.doe@example.com',
        'password' => 'password',
    ])->assertStatus(200);
})->skip();

test('the first registered user becomes admin of the default group', function (): void {
    $group = Group::first() ?? Group::factory()->create();

    $this->post('/register', [
        'first_name' => 'Max',
        'last_name' => 'Mustermann',
        'email' => 'max.mustermann@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect('/dashboard');

    $user = User::firstOrFail();

    expect($user->belongsToGroup($group))->toBeTrue();
    expect($user->isGroupAdmin($group))->toBeTrue();

    $this->assertDatabaseHas('group_user', [
        'group_id' => $group->id,
        'user_id' => $user->id,
        'is_admin' => true,
    ]);
});
