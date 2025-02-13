<?php

use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->globalAdmin = User::factory()->create(['is_admin' => true]);
    $this->normalUser = User::factory()->create(['is_admin' => false]);

    // Create group hierarchy
    $this->mainGroup = Group::create([
        'name' => 'Main Group',
        'description' => 'Main Group Description',
    ]);

    $this->subGroup = Group::create([
        'name' => 'Sub Group',
        'description' => 'Sub Group Description',
        'parent_id' => $this->mainGroup->id,
    ]);

    // Create a separate group hierarchy
    $this->otherMainGroup = Group::create([
        'name' => 'Other Main Group',
        'description' => 'Other Main Group Description',
    ]);

    $this->otherSubGroup = Group::create([
        'name' => 'Other Sub Group',
        'description' => 'Other Sub Group Description',
        'parent_id' => $this->otherMainGroup->id,
    ]);
});

test('global admin can manage all groups', function () {
    expect($this->globalAdmin->can('viewAny', Group::class))->toBeTrue()
        ->and($this->globalAdmin->can('view', $this->mainGroup))->toBeTrue()
        ->and($this->globalAdmin->can('create', Group::class))->toBeTrue()
        ->and($this->globalAdmin->can('create', [Group::class, $this->mainGroup]))->toBeTrue()
        ->and($this->globalAdmin->can('update', $this->mainGroup))->toBeTrue()
        ->and($this->globalAdmin->can('delete', $this->mainGroup))->toBeTrue()
        ->and($this->globalAdmin->can('manageUsers', $this->mainGroup))->toBeTrue()
        ->and($this->globalAdmin->can('manageArea', $this->mainGroup))->toBeTrue();
});

test('initiative admin can manage their group and subgroups', function () {
    // Make normal user admin of main group
    $this->mainGroup->users()->attach($this->normalUser->id, ['is_admin' => true]);

    // Can view groups in their hierarchy
    expect($this->normalUser->can('viewAny', Group::class))->toBeTrue()
        ->and($this->normalUser->can('view', $this->mainGroup))->toBeTrue()
        ->and($this->normalUser->can('view', $this->subGroup))->toBeTrue();

    // Cannot view groups outside their hierarchy
    expect($this->normalUser->can('view', $this->otherMainGroup))->toBeFalse()
        ->and($this->normalUser->can('view', $this->otherSubGroup))->toBeFalse();

    // Can manage own group and subgroups
    expect($this->normalUser->can('update', $this->mainGroup))->toBeTrue()
        ->and($this->normalUser->can('update', $this->subGroup))->toBeTrue()
        ->and($this->normalUser->can('create', [Group::class, $this->mainGroup]))->toBeTrue()
        ->and($this->normalUser->can('create', [Group::class, $this->subGroup]))->toBeTrue()
        ->and($this->normalUser->can('manageUsers', $this->mainGroup))->toBeTrue()
        ->and($this->normalUser->can('manageUsers', $this->subGroup))->toBeTrue()
        ->and($this->normalUser->can('manageArea', $this->mainGroup))->toBeTrue()
        ->and($this->normalUser->can('manageArea', $this->subGroup))->toBeTrue();

    // Cannot manage groups outside their hierarchy
    expect($this->normalUser->can('update', $this->otherMainGroup))->toBeFalse()
        ->and($this->normalUser->can('create', [Group::class, $this->otherMainGroup]))->toBeFalse()
        ->and($this->normalUser->can('manageUsers', $this->otherMainGroup))->toBeFalse()
        ->and($this->normalUser->can('manageArea', $this->otherMainGroup))->toBeFalse();

    // Cannot create root groups
    expect($this->normalUser->can('create', Group::class))->toBeFalse();

    // Cannot delete groups (only global admin can)
    expect($this->normalUser->can('delete', $this->mainGroup))->toBeFalse()
        ->and($this->normalUser->can('delete', $this->subGroup))->toBeFalse();
});

test('subgroup admin cannot manage parent groups', function () {
    // Make normal user admin of sub group
    $this->subGroup->users()->attach($this->normalUser->id, ['is_admin' => true]);

    // Can view groups in their hierarchy
    expect($this->normalUser->can('viewAny', Group::class))->toBeTrue()
        ->and($this->normalUser->can('view', $this->mainGroup))->toBeTrue()
        ->and($this->normalUser->can('view', $this->subGroup))->toBeTrue();

    // Cannot view groups outside their hierarchy
    expect($this->normalUser->can('view', $this->otherMainGroup))->toBeFalse()
        ->and($this->normalUser->can('view', $this->otherSubGroup))->toBeFalse();

    // Can only manage own group
    expect($this->normalUser->can('update', $this->mainGroup))->toBeFalse()
        ->and($this->normalUser->can('update', $this->subGroup))->toBeTrue()
        ->and($this->normalUser->can('manageUsers', $this->mainGroup))->toBeFalse()
        ->and($this->normalUser->can('manageUsers', $this->subGroup))->toBeTrue()
        ->and($this->normalUser->can('manageArea', $this->mainGroup))->toBeFalse()
        ->and($this->normalUser->can('manageArea', $this->subGroup))->toBeTrue();

    // Can create subgroups under own group
    expect($this->normalUser->can('create', [Group::class, $this->subGroup]))->toBeTrue();

    // Cannot create groups outside their hierarchy
    expect($this->normalUser->can('create', Group::class))->toBeFalse()
        ->and($this->normalUser->can('create', [Group::class, $this->mainGroup]))->toBeFalse()
        ->and($this->normalUser->can('create', [Group::class, $this->otherMainGroup]))->toBeFalse();
});

test('normal user can only view groups they belong to', function () {
    // Add user to sub group
    $this->subGroup->users()->attach($this->normalUser->id, ['is_admin' => false]);

    // Can view groups they belong to
    expect($this->normalUser->can('view', $this->subGroup))->toBeTrue();

    // Cannot view groups they don't belong to
    expect($this->normalUser->can('view', $this->mainGroup))->toBeFalse()
        ->and($this->normalUser->can('view', $this->otherMainGroup))->toBeFalse()
        ->and($this->normalUser->can('view', $this->otherSubGroup))->toBeFalse();

    // Cannot manage any groups
    expect($this->normalUser->can('create', Group::class))->toBeFalse()
        ->and($this->normalUser->can('create', [Group::class, $this->subGroup]))->toBeFalse()
        ->and($this->normalUser->can('update', $this->subGroup))->toBeFalse()
        ->and($this->normalUser->can('delete', $this->subGroup))->toBeFalse()
        ->and($this->normalUser->can('manageUsers', $this->subGroup))->toBeFalse()
        ->and($this->normalUser->can('manageArea', $this->subGroup))->toBeFalse();
});

test('group admin is also a regular member of the group', function () {
    // Make normal user admin of main group
    $this->mainGroup->users()->attach($this->normalUser->id, ['is_admin' => true]);
    
    // Check that the user belongs to the group
    expect($this->normalUser->belongsToGroup($this->mainGroup))->toBeTrue()
        ->and($this->mainGroup->users()->pluck('users.id'))->toContain($this->normalUser->id)
        ->and($this->mainGroup->admins()->pluck('users.id'))->toContain($this->normalUser->id);
}); 