<?php

use App\Models\Group;
use App\Models\User;
use App\Services\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->admin = User::factory()->admin()->create();
});

test('group users shows the is_admin status of the current group, not the first group', function (): void {
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
        ->assertInertia(fn (Assert $page): Assert => $page
            ->component('Groups/Index')
            ->where('groupUsers', fn (Collection $users): bool => $users
                ->firstWhere('name', 'John Doe')['is_admin'] === true
            )
        );

    // Viewing group 2 -> same John is NOT admin
    app(SessionService::class)->actAsGroup($group2, true);
    $this->actingAs($this->admin)
        ->get(route('groups.show', $group2))
        ->assertInertia(fn (Assert $page): Assert => $page
            ->component('Groups/Index')
            ->where('groupUsers', fn (Collection $users): bool => $users
                ->firstWhere('name', 'John Doe')['is_admin'] === false
            )
        );
});

test('group users only contains active members of the group', function (): void {
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
        ->assertInertia(fn (Assert $page): Assert => $page
            ->where('groupUsers', function (Collection $users) use ($activeMember, $inactiveMember, $otherGroupMember): bool {
                $names = $users->pluck('name');

                return $names->contains($activeMember->name)
                    && ! $names->contains($inactiveMember->name)
                    && ! $names->contains($otherGroupMember->name);
            })
        );
});

test('group users dto has the expected shape', function (): void {
    $group = Group::factory()->create();
    $group->users()->attach($this->admin->id, ['is_admin' => true]);

    app(SessionService::class)->actAsGroup($group, true);
    $this->actingAs($this->admin)
        ->get(route('groups.show', $group))
        ->assertInertia(fn (Assert $page): Assert => $page
            ->has('groupUsers', 1)
            ->has('groupUsers.0', fn (Assert $user): Assert => $user
                ->where('id', $this->admin->uuid)
                ->where('name', $this->admin->name)
                ->where('is_admin', true)
                ->where('is_active', true)
                ->etc()
            )
        );
});

test('all users only contains active users with id, name and email', function (): void {
    $group = Group::factory()->create();
    $group->users()->attach($this->admin->id, ['is_admin' => true]);

    $active = User::factory()->create(['is_active' => true]);
    $inactive = User::factory()->create(['is_active' => false]);

    app(SessionService::class)->actAsGroup($group, true);
    $this->actingAs($this->admin)
        ->get(route('groups.show', $group))
        ->assertInertia(fn (Assert $page): Assert => $page
            ->where('allUsers', function (Collection $users) use ($active, $inactive): bool {
                $ids = $users->pluck('id');

                return $ids->contains($active->uuid) && ! $ids->contains($inactive->uuid);
            })
            ->has('allUsers.0', fn (Assert $user): Assert => $user
                ->hasAll(['id', 'name', 'email'])
                ->missing('is_admin')
            )
        );
});

test('non-admin cannot view the group users list', function (): void {
    $groupAdmin = User::factory()->create();
    $member = User::factory()->create();
    $group = Group::factory()->create();
    $group->users()->attach($groupAdmin->id, ['is_admin' => true]);
    $group->users()->attach($member->id, ['is_admin' => false]);

    Config::set('app.group_context', 'global');

    $this->actingAs($member)
        ->get(route('groups.show', $group))
        ->assertForbidden();
});

test('admin can add a user to the group as admin', function (): void {
    $groupAdmin = User::factory()->create();
    $candidate = User::factory()->create();
    $group = Group::factory()->create();
    $group->users()->attach($groupAdmin->id, ['is_admin' => true]);

    Config::set('app.group_context', 'global');

    $this->actingAs($groupAdmin)
        ->post(route('groups.users.store', $group), [
            'id' => $candidate->uuid,
            'is_admin' => true,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('group_user', [
        'group_id' => $group->id,
        'user_id' => $candidate->id,
        'is_admin' => true,
    ]);
});

test('non-admin cannot add users to the group', function (): void {
    $groupAdmin = User::factory()->create();
    $member = User::factory()->create();
    $candidate = User::factory()->create();
    $group = Group::factory()->create();
    $group->users()->attach($groupAdmin->id, ['is_admin' => true]);
    $group->users()->attach($member->id, ['is_admin' => false]);

    Config::set('app.group_context', 'global');

    $this->actingAs($member)
        ->post(route('groups.users.store', $group), [
            'id' => $candidate->uuid,
            'is_admin' => false,
        ])
        ->assertForbidden();

    $this->assertDatabaseMissing('group_user', [
        'group_id' => $group->id,
        'user_id' => $candidate->id,
    ]);
});

test('non-admin cannot update a user role in the group', function (): void {
    $groupAdmin = User::factory()->create();
    $member = User::factory()->create();
    $group = Group::factory()->create();
    $group->users()->attach($groupAdmin->id, ['is_admin' => true]);
    $group->users()->attach($member->id, ['is_admin' => false]);

    Config::set('app.group_context', 'global');

    $this->actingAs($member)
        ->put(route('groups.users.update', [$group, $groupAdmin]), [
            'is_admin' => false,
        ])
        ->assertForbidden();

    $this->assertDatabaseHas('group_user', [
        'group_id' => $group->id,
        'user_id' => $groupAdmin->id,
        'is_admin' => true,
    ]);
});

test('non-admin cannot remove a user from the group', function (): void {
    $groupAdmin = User::factory()->create();
    $member = User::factory()->create();
    $group = Group::factory()->create();
    $group->users()->attach($groupAdmin->id, ['is_admin' => true]);
    $group->users()->attach($member->id, ['is_admin' => false]);

    Config::set('app.group_context', 'global');

    $this->actingAs($member)
        ->delete(route('groups.users.destroy', [$group, $groupAdmin]))
        ->assertForbidden();

    $this->assertDatabaseHas('group_user', [
        'group_id' => $group->id,
        'user_id' => $groupAdmin->id,
    ]);
});

test('cannot add the same user twice to a group', function (): void {
    $groupAdmin = User::factory()->create();
    $candidate = User::factory()->create();
    $group = Group::factory()->create();
    $group->users()->attach($groupAdmin->id, ['is_admin' => true]);
    $group->users()->attach($candidate->id, ['is_admin' => false]);

    Config::set('app.group_context', 'global');

    $this->actingAs($groupAdmin)
        ->post(route('groups.users.store', $group), [
            'id' => $candidate->uuid,
            'is_admin' => false,
        ])
        ->assertSessionHasErrors('id');
});
