<?php

use App\Models\Group;
use App\Models\User;

use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\put;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->user = User::factory()->create();
    $this->group = Group::factory()->create();
    
    // Make admin user a group admin
    $this->group->users()->attach($this->admin->id, ['is_admin' => true]);
});

test('admin can list users in a group', function () {
    // Arrange
    $regularUser = User::factory()->create();
    $this->group->users()->attach($regularUser->id, ['is_admin' => false]);
    
    // Act & Assert
    actingAs($this->admin)
        ->get("/api/groups/{$this->group->id}/users")
        ->assertOk()
        ->assertJsonCount(2, 'data') // admin + regular user
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'email',
                    'is_admin'
                ]
            ]
        ]);
});

test('non-admin cannot list users in a group', function () {
    actingAs($this->user)
        ->get("/api/groups/{$this->group->id}/users")
        ->assertForbidden();
});

test('admin can add user to group', function () {
    actingAs($this->admin)
        ->post("/api/groups/{$this->group->id}/users", [
            'id' => $this->user->id,
            'is_admin' => false
        ])
        ->assertOk()
        ->assertJsonStructure([
            'id',
            'name',
            'email',
            'is_admin'
        ])
        ->assertJson([
            'id' => $this->user->id,
            'is_admin' => false
        ]);

    $this->assertDatabaseHas('group_user', [
        'group_id' => $this->group->id,
        'user_id' => $this->user->id,
        'is_admin' => false
    ]);
});

test('admin can add user as admin to group', function () {
    actingAs($this->admin)
        ->post("/api/groups/{$this->group->id}/users", [
            'id' => $this->user->id,
            'is_admin' => true
        ])
        ->assertOk()
        ->assertJson([
            'id' => $this->user->id,
            'is_admin' => true
        ]);
});

test('non-admin cannot add users to group', function () {
    actingAs($this->user)
        ->post("/api/groups/{$this->group->id}/users", [
            'user_id' => User::factory()->create()->id
        ])
        ->assertForbidden();
});

test('admin can update user role in group', function () {
    // Arrange
    $this->group->users()->attach($this->user->id, ['is_admin' => false]);
    
    // Act & Assert
    actingAs($this->admin)
        ->put("/api/groups/{$this->group->id}/users/{$this->user->id}", [
            'is_admin' => true
        ])
        ->assertOk()
        ->assertJson([
            'id' => $this->user->id,
            'is_admin' => true
        ]);

    $this->assertDatabaseHas('group_user', [
        'group_id' => $this->group->id,
        'user_id' => $this->user->id,
        'is_admin' => true
    ]);
});

test('non-admin cannot update user role in group', function () {
    $this->group->users()->attach($this->user->id, ['is_admin' => false]);
    
    actingAs($this->user)
        ->put("/api/groups/{$this->group->id}/users/{$this->admin->id}", [
            'is_admin' => false
        ])
        ->assertForbidden();
});

test('admin can remove user from group', function () {
    // Arrange
    $this->group->users()->attach($this->user->id, ['is_admin' => false]);
    
    // Act & Assert
    actingAs($this->admin)
        ->delete("/api/groups/{$this->group->id}/users/{$this->user->id}")
        ->assertNoContent();

    $this->assertDatabaseMissing('group_user', [
        'group_id' => $this->group->id,
        'user_id' => $this->user->id
    ]);
});

test('non-admin cannot remove user from group', function () {
    $this->group->users()->attach($this->user->id, ['is_admin' => false]);
    
    actingAs($this->user)
        ->delete("/api/groups/{$this->group->id}/users/{$this->admin->id}")
        ->assertForbidden();
});

test('cannot add same user twice to group', function () {
    // First add
    actingAs($this->admin)
        ->post("/api/groups/{$this->group->id}/users", [
            'user_id' => $this->user->id,
            'is_admin' => false
        ]);

    // Try to add again
    actingAs($this->admin)
        ->withHeaders(['Accept' => 'application/json'])
        ->post("/api/groups/{$this->group->id}/users", [
            'user_id' => $this->user->id,
            'is_admin' => false
        ])
        ->assertUnprocessable();
}); 