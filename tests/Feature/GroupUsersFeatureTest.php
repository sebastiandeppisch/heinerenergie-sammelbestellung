<?php

use App\Models\Group;
use App\Models\User;
use App\Services\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

test('group users shows the is_admin status of the current group, not the first group', function () {
    // A user that belongs to two groups with DIFFERENT admin status
    $group1 = Group::factory()->create(['name' => 'Group 1']);
    $group2 = Group::factory()->create(['name' => 'Group 2']);

    $member = User::factory()->create(['first_name' => 'John', 'last_name' => 'Doe']);
    $group1->users()->attach($member->id, ['is_admin' => true]);   // admin in group 1
    $group2->users()->attach($member->id, ['is_admin' => false]);  // NOT admin in group 2

    $group1->users()->attach($this->admin->id, ['is_admin' => true]);
    $group2->users()->attach($this->admin->id, ['is_admin' => true]);

    // Viewing group 1 -> John is admin
    app(SessionService::class)->actAsGroup($group1, true);
    $this->actingAs($this->admin)
        ->get(route('groups.show', $group1))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Groups/Index')
            ->where('groupUsers', fn ($users) => collect($users)
                ->firstWhere('name', 'John Doe')['is_admin'] === true
            )
        );

    // Viewing group 2 -> same John is NOT admin
    app(SessionService::class)->actAsGroup($group2, true);
    $this->actingAs($this->admin)
        ->get(route('groups.show', $group2))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Groups/Index')
            ->where('groupUsers', fn ($users) => collect($users)
                ->firstWhere('name', 'John Doe')['is_admin'] === false
            )
        );
});

test('group users only contains active members of the group', function () {
    $group = Group::factory()->create();
    $otherGroup = Group::factory()->create();

    $group->users()->attach($this->admin->id, ['is_admin' => true]);

    $activeMember = User::factory()->create(['first_name' => 'Active', 'last_name' => 'Member', 'is_active' => true]);
    $inactiveMember = User::factory()->create(['first_name' => 'Inactive', 'last_name' => 'Member', 'is_active' => false]);
    $otherGroupMember = User::factory()->create(['first_name' => 'Other', 'last_name' => 'Member']);

    $group->users()->attach($activeMember->id, ['is_admin' => false]);
    $group->users()->attach($inactiveMember->id, ['is_admin' => false]);
    $otherGroup->users()->attach($otherGroupMember->id, ['is_admin' => false]);

    app(SessionService::class)->actAsGroup($group, true);
    $this->actingAs($this->admin)
        ->get(route('groups.show', $group))
        ->assertInertia(fn (Assert $page) => $page
            ->where('groupUsers', function ($users) use ($activeMember, $inactiveMember, $otherGroupMember) {
                $names = collect($users)->pluck('name');

                return $names->contains($activeMember->name)
                    && ! $names->contains($inactiveMember->name)
                    && ! $names->contains($otherGroupMember->name);
            })
        );
});

test('group users dto has the expected shape', function () {
    $group = Group::factory()->create();
    $group->users()->attach($this->admin->id, ['is_admin' => true]);

    app(SessionService::class)->actAsGroup($group, true);
    $this->actingAs($this->admin)
        ->get(route('groups.show', $group))
        ->assertInertia(fn (Assert $page) => $page
            ->has('groupUsers', 1)
            ->has('groupUsers.0', fn (Assert $user) => $user
                ->where('id', $this->admin->uuid)
                ->where('name', $this->admin->name)
                ->where('is_admin', true)
                ->where('is_active', true)
                ->etc()
            )
        );
});

test('all users only contains active users with id, name and email', function () {
    $group = Group::factory()->create();
    $group->users()->attach($this->admin->id, ['is_admin' => true]);

    $active = User::factory()->create(['is_active' => true]);
    $inactive = User::factory()->create(['is_active' => false]);

    app(SessionService::class)->actAsGroup($group, true);
    $this->actingAs($this->admin)
        ->get(route('groups.show', $group))
        ->assertInertia(fn (Assert $page) => $page
            ->where('allUsers', function ($users) use ($active, $inactive) {
                $ids = collect($users)->pluck('id');

                return $ids->contains($active->uuid) && ! $ids->contains($inactive->uuid);
            })
            ->has('allUsers.0', fn (Assert $user) => $user
                ->hasAll(['id', 'name', 'email'])
                ->missing('is_admin')
            )
        );
});
