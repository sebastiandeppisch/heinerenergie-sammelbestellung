<?php

use App\Models\User;
use App\Models\Group;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);


beforeEach(function () {
    $this->user = User::factory()->create();
    $this->group = Group::factory()->create();
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->group->users()->attach($this->admin, ['is_admin' => true]);
});

test('system admin can change password of other users', function () {
    $this->withoutExceptionHandling();
        $this->actingAs($this->admin)
        ->post('/actAsSystemAdmin');

    $this->put(route('users.changePassword', $this->user), ['password' => 'new-password'])
        ->assertSessionHasNoErrors();

    $user = $this->user->fresh();

    $this->assertTrue(Hash::check('new-password', $user->password));
});

test('group admins can not change password of other users', function () {
    $this->actingAs($this->admin)
        ->post("/actAsGroup/{$this->group->uuid}", ['asAdmin' => true]);

    $this->put(route('users.changePassword', $this->user), ['password' => 'new-password'])
        ->assertStatus(403);
});