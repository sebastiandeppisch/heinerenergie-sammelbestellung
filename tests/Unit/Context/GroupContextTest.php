<?php

namespace Tests\Unit\Context;

use App\Context\GroupContext;
use App\Models\Advice;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create users
    $this->systemAdmin = User::factory()->create(['is_admin' => true]);
    $this->normalUser = User::factory()->create(['is_admin' => false]);

    // Create deep group hierarchy
    $this->mainGroup = Group::create([
        'name' => 'Main Group',
        'description' => 'Main Group Description',
    ]);

    $this->subGroup = Group::create([
        'name' => 'Sub Group',
        'description' => 'Sub Group Description',
        'parent_id' => $this->mainGroup->id,
    ]);

    $this->subSubGroup = Group::create([
        'name' => 'Sub Sub Group',
        'description' => 'Sub Sub Group Description',
        'parent_id' => $this->subGroup->id,
    ]);

    // Create parallel hierarchy
    $this->otherGroup = Group::create([
        'name' => 'Other Group',
        'description' => 'Other Group Description',
    ]);

    $this->otherSubGroup = Group::create([
        'name' => 'Other Sub Group',
        'description' => 'Other Sub Group Description',
        'parent_id' => $this->otherGroup->id,
    ]);
});

test('system admin is not group admin => being implicit group admin is concern of Policy, not GroupContext', function () {
    $context = new GroupContext(null, true, $this->systemAdmin);

    expect($context->isActingAsSystemAdmin($this->systemAdmin))->toBeTrue()
        ->and($context->isActingAsTransitiveMemberOrAdmin($this->systemAdmin, $this->mainGroup))->toBeFalse()
        ->and($context->isActingAsTransitiveMemberOrAdmin($this->systemAdmin, $this->subGroup))->toBeFalse()
        ->and($context->isActingAsTransitiveMemberOrAdmin($this->systemAdmin, $this->subSubGroup))->toBeFalse()
        ->and($context->isActingAsDirectAdmin($this->systemAdmin, $this->mainGroup))->toBeFalse()
        ->and($context->isActingAsDirectAdmin($this->systemAdmin, $this->subGroup))->toBeFalse()
        ->and($context->isActingAsDirectAdmin($this->systemAdmin, $this->subSubGroup))->toBeFalse();
});

test('normal user has no access without group membership', function () {
    $context = new GroupContext(null, false, $this->normalUser);

    expect($context->isActingAsSystemAdmin($this->normalUser))->toBeFalse()
        ->and($context->isActingAsTransitiveMemberOrAdmin($this->normalUser, $this->mainGroup))->toBeFalse()
        ->and($context->isActingAsTransitiveMemberOrAdmin($this->normalUser, $this->subGroup))->toBeFalse()
        ->and($context->isActingAsTransitiveMemberOrAdmin($this->normalUser, $this->subSubGroup))->toBeFalse()
        ->and($context->isActingAsDirectAdmin($this->normalUser, $this->mainGroup))->toBeFalse()
        ->and($context->isActingAsDirectAdmin($this->normalUser, $this->subGroup))->toBeFalse()
        ->and($context->isActingAsDirectAdmin($this->normalUser, $this->subSubGroup))->toBeFalse();
});

test('group admin has access to group and subgroups', function () {
    // Make normal user admin of main group
    $this->mainGroup->users()->attach($this->normalUser->id, ['is_admin' => true]);

    $context = new GroupContext($this->mainGroup, false, $this->normalUser, true);

    expect($context->isActingAsSystemAdmin($this->normalUser))->toBeFalse()
        ->and($context->isActingAsTransitiveMemberOrAdmin($this->normalUser, $this->mainGroup))->toBeTrue()
        ->and($context->isActingAsTransitiveMemberOrAdmin($this->normalUser, $this->subGroup))->toBeTrue()
        ->and($context->isActingAsTransitiveMemberOrAdmin($this->normalUser, $this->subSubGroup))->toBeTrue()
        ->and($context->isActingAsDirectAdmin($this->normalUser, $this->mainGroup))->toBeTrue()
        ->and($context->isActingAsDirectAdmin($this->normalUser, $this->subGroup))->toBeTrue()
        ->and($context->isActingAsDirectAdmin($this->normalUser, $this->subSubGroup))->toBeTrue();
});

test('temporary group admin has scoped access', function () {
    // Make normal user member of main group
    $this->mainGroup->users()->attach($this->normalUser->id, ['is_admin' => false]);

    $context = new GroupContext($this->mainGroup, false, $this->normalUser, true);

    expect($context->getCurrentGroup()->id)->toBe($this->mainGroup->id)
        ->and($context->isActingAsDirectAdmin($this->normalUser, $this->mainGroup))->toBeTrue()
        ->and($context->isActingAsTransitiveMemberOrAdmin($this->normalUser, $this->mainGroup))->toBeTrue()
        ->and($context->isActingAsTransitiveMemberOrAdmin($this->normalUser, $this->subGroup))->toBeTrue()
        ->and($context->isActingAsTransitiveMemberOrAdmin($this->normalUser, $this->subSubGroup))->toBeTrue()
        ->and($context->isActingAsDirectAdmin($this->normalUser, $this->mainGroup))->toBeTrue()
        ->and($context->isActingAsDirectAdmin($this->normalUser, $this->subGroup))->toBeTrue()
        ->and($context->isActingAsDirectAdmin($this->normalUser, $this->subSubGroup))->toBeTrue();
});


test('admin of sub group cannot access parent groups', function () {
    // Make normal user admin of sub group only
    $this->subGroup->users()->attach($this->normalUser->id, ['is_admin' => true]);

    $context = new GroupContext($this->subGroup, false, $this->normalUser, true);

    expect($context->isActingAsSystemAdmin($this->normalUser))->toBeFalse()
        ->and($context->isActingAsTransitiveMemberOrAdmin($this->normalUser, $this->mainGroup))->toBeFalse()
        ->and($context->isActingAsTransitiveMemberOrAdmin($this->normalUser, $this->subGroup))->toBeTrue()
        ->and($context->isActingAsTransitiveMemberOrAdmin($this->normalUser, $this->subSubGroup))->toBeTrue()
        ->and($context->isActingAsDirectAdmin($this->normalUser, $this->mainGroup))->toBeFalse()
        ->and($context->isActingAsDirectAdmin($this->normalUser, $this->subGroup))->toBeTrue()
        ->and($context->isActingAsDirectAdmin($this->normalUser, $this->subSubGroup))->toBeTrue();
});

test('user with multiple group memberships has correct access', function () {
    // Make user member of multiple groups with different roles
    $this->mainGroup->users()->attach($this->normalUser->id, ['is_admin' => false]);
    $this->otherGroup->users()->attach($this->normalUser->id, ['is_admin' => true]);

    $context = new GroupContext($this->mainGroup, false, $this->normalUser);

    // Should have normal access to main group hierarchy
    expect($context->isActingAsTransitiveMemberOrAdmin($this->normalUser, $this->mainGroup))->toBeTrue()
        ->and($context->isActingAsTransitiveMemberOrAdmin($this->normalUser, $this->subGroup))->toBeTrue()
        ->and($context->isActingAsTransitiveMemberOrAdmin($this->normalUser, $this->subSubGroup))->toBeTrue()
        ->and($context->isActingAsDirectAdmin($this->normalUser, $this->mainGroup))->toBeFalse()
        ->and($context->isActingAsDirectAdmin($this->normalUser, $this->subGroup))->toBeFalse()
        ->and($context->isActingAsDirectAdmin($this->normalUser, $this->subSubGroup))->toBeFalse();

    // without swichting the context, the normal user should not have access to the other group

    expect($context->isActingAsTransitiveMemberOrAdmin($this->normalUser, $this->otherGroup))->toBeFalse()
        ->and($context->isActingAsTransitiveMemberOrAdmin($this->normalUser, $this->otherSubGroup))->toBeFalse()
        ->and($context->isActingAsDirectAdmin($this->normalUser, $this->otherGroup))->toBeFalse()
        ->and($context->isActingAsDirectAdmin($this->normalUser, $this->otherSubGroup))->toBeFalse();

    $context = new GroupContext($this->otherGroup, false, $this->normalUser, true);

    // Should have admin access to other group hierarchy
    expect($context->isActingAsTransitiveMemberOrAdmin($this->normalUser, $this->otherGroup))->toBeTrue()
        ->and($context->isActingAsTransitiveMemberOrAdmin($this->normalUser, $this->otherSubGroup))->toBeTrue()
        ->and($context->isActingAsDirectAdmin($this->normalUser, $this->otherGroup))->toBeTrue()
        ->and($context->isActingAsDirectAdmin($this->normalUser, $this->otherSubGroup))->toBeTrue();

    // again without swichting the context, the normal user should not have access to the main group
    expect($context->isActingAsTransitiveMemberOrAdmin($this->normalUser, $this->mainGroup))->toBeFalse()
        ->and($context->isActingAsTransitiveMemberOrAdmin($this->normalUser, $this->subGroup))->toBeFalse()
        ->and($context->isActingAsTransitiveMemberOrAdmin($this->normalUser, $this->subSubGroup))->toBeFalse()
        ->and($context->isActingAsDirectAdmin($this->normalUser, $this->mainGroup))->toBeFalse()
        ->and($context->isActingAsDirectAdmin($this->normalUser, $this->subGroup))->toBeFalse()
        ->and($context->isActingAsDirectAdmin($this->normalUser, $this->subSubGroup))->toBeFalse();
});

test('temporary admin access works independently for different groups', function () {
    // Make user member of both groups
    $this->mainGroup->users()->attach($this->normalUser->id, ['is_admin' => false]);
    $this->otherGroup->users()->attach($this->normalUser->id, ['is_admin' => false]);

    // Set up context with main group and admin rights
    $context1 = new GroupContext($this->mainGroup, false, $this->normalUser, true);

    expect($context1->isActingAsDirectAdmin($this->normalUser, $this->mainGroup))->toBeTrue()
        ->and($context1->isActingAsDirectAdmin($this->normalUser, $this->subGroup))->toBeTrue()
        ->and($context1->isActingAsDirectAdmin($this->normalUser, $this->subSubGroup))->toBeTrue();

    // Switch to other group context without admin rights
    $context2 = new GroupContext($this->otherGroup, false, $this->normalUser, false);

    expect($context2->isActingAsDirectAdmin($this->normalUser, $this->otherGroup))->toBeFalse()
        ->and($context2->isActingAsDirectAdmin($this->normalUser, $this->otherSubGroup))->toBeFalse();
});

test('access is denied for null user', function () {
    $context = new GroupContext($this->mainGroup, false, null);

    $context->isActingAsTransitiveMemberOrAdmin($this->normalUser, $this->mainGroup);
})->throws(InvalidArgumentException::class);


test('admin of parent group has access to child group', function () {
    // Make user admin of main group
    $this->mainGroup->users()->attach($this->normalUser->id, ['is_admin' => true]);

    $context = new GroupContext($this->mainGroup, false, $this->normalUser, true);

    expect($context->isActingAsTransitiveAdmin($this->normalUser, $this->subSubGroup))->toBeTrue();
});
