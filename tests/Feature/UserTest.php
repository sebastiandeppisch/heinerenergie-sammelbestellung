<?php

use App\Models\User;
use App\Models\Group;
use App\Services\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->group = Group::factory()->create(['name' => 'Test Initiative']);
    $this->group->users()->attach($this->user, ['is_admin' => true]);
    app(SessionService::class)->actAsGroup($this->group, true);
    $this->actingAs($this->user);

});

test('non system admin cannot promote users to system admin', function () {
    $userToPromote = User::factory()->create([
        'is_admin' => false,
    ]);

    $response = $this->put(route('users.update', $userToPromote), ['is_admin' => true]);
    $this->assertFalse($userToPromote->fresh()->is_admin);
});

test('system admin can promote users to system admin', function () {
    app(SessionService::class)->actAsSystemAdmin();


    $userToPromote = User::factory()->create([
        'is_admin' => false,
    ]);

    $response = $this->put(route('users.update', $userToPromote), ['is_admin' => true]);
    $this->assertTrue($userToPromote->fresh()->is_admin);
});

test('non system admin cannot create system admin users', function () {
    $response = $this->post(route('users.store'), [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john.doe@example.com',
        'is_admin' => true,
    ]);

    $createdUser = User::where('email', 'john.doe@example.com')->first();
    
    $this->assertNotNull($createdUser);

    $this->assertFalse($createdUser->is_admin);
}); 

test('system admin can create system admin users', function () {
    $this->user->is_admin = true;
    $this->user->save();

    app(SessionService::class)->actAsSystemAdmin();

    $response = $this->post(route('users.store'), [
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => 'jane.doe@example.com',
        'is_admin' => true,
    ]);

    $createdUser = User::where('email', 'jane.doe@example.com')->first();
    $this->assertNotNull($createdUser);
    $this->assertTrue($createdUser->is_admin);
});