<?php

namespace Tests\Unit\Context;

use Tests\TestCase;
use App\Models\User;
use App\Models\Group;
use App\Models\Advice;
use App\Context\GroupContext;
use InvalidArgumentException;
use App\Context\GroupContextContract;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function() {
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

test('system admin has full access', function() {
    $context = new GroupContext(null, true, $this->systemAdmin);

    expect($context->actsAsSystemAdmin($this->systemAdmin))->toBeTrue()
        ->and($context->hasAccessToGroup($this->systemAdmin, $this->mainGroup))->toBeTrue()
        ->and($context->hasAccessToGroup($this->systemAdmin, $this->subGroup))->toBeTrue()
        ->and($context->hasAccessToGroup($this->systemAdmin, $this->subSubGroup))->toBeTrue()
        ->and($context->actsAsGroupAdmin($this->systemAdmin, $this->mainGroup))->toBeTrue()
        ->and($context->actsAsGroupAdmin($this->systemAdmin, $this->subGroup))->toBeTrue()
        ->and($context->actsAsGroupAdmin($this->systemAdmin, $this->subSubGroup))->toBeTrue();
});

test('normal user has no access without group membership', function() {
    $context = new GroupContext(null, false, $this->normalUser);

    expect($context->actsAsSystemAdmin($this->normalUser))->toBeFalse()
        ->and($context->hasAccessToGroup($this->normalUser, $this->mainGroup))->toBeFalse()
        ->and($context->hasAccessToGroup($this->normalUser, $this->subGroup))->toBeFalse()
        ->and($context->hasAccessToGroup($this->normalUser, $this->subSubGroup))->toBeFalse()
        ->and($context->actsAsGroupAdmin($this->normalUser, $this->mainGroup))->toBeFalse()
        ->and($context->actsAsGroupAdmin($this->normalUser, $this->subGroup))->toBeFalse()
        ->and($context->actsAsGroupAdmin($this->normalUser, $this->subSubGroup))->toBeFalse();
});

test('group admin has access to group and subgroups', function() {
    // Make normal user admin of main group
    $this->mainGroup->users()->attach($this->normalUser->id, ['is_admin' => true]);
    
    $context = new GroupContext($this->mainGroup, false, $this->normalUser, true);

    expect($context->actsAsSystemAdmin($this->normalUser))->toBeFalse()
        ->and($context->hasAccessToGroup($this->normalUser, $this->mainGroup))->toBeTrue()
        ->and($context->hasAccessToGroup($this->normalUser, $this->subGroup))->toBeTrue()
        ->and($context->hasAccessToGroup($this->normalUser, $this->subSubGroup))->toBeTrue()
        ->and($context->actsAsGroupAdmin($this->normalUser, $this->mainGroup))->toBeTrue()
        ->and($context->actsAsGroupAdmin($this->normalUser, $this->subGroup))->toBeTrue()
        ->and($context->actsAsGroupAdmin($this->normalUser, $this->subSubGroup))->toBeTrue();
});

test('temporary group admin has scoped access', function() {
    // Make normal user member of main group
    $this->mainGroup->users()->attach($this->normalUser->id, ['is_admin' => false]);
    
    $context = new GroupContext($this->mainGroup, false, $this->normalUser, true);

    expect($context->getCurrentGroup()->id)->toBe($this->mainGroup->id)
        ->and($context->actsAsGroupAdmin($this->normalUser, $this->mainGroup))->toBeTrue()
        ->and($context->hasAccessToGroup($this->normalUser, $this->mainGroup))->toBeTrue()
        ->and($context->hasAccessToGroup($this->normalUser, $this->subGroup))->toBeTrue()
        ->and($context->hasAccessToGroup($this->normalUser, $this->subSubGroup))->toBeTrue()
        ->and($context->actsAsGroupAdmin($this->normalUser, $this->mainGroup))->toBeTrue()
        ->and($context->actsAsGroupAdmin($this->normalUser, $this->subGroup))->toBeTrue()
        ->and($context->actsAsGroupAdmin($this->normalUser, $this->subSubGroup))->toBeTrue();
});

test('advice access is group scoped', function() {
    $mainGroupAdvice = Advice::factory()->create(['group_id' => $this->mainGroup->id]);
    $otherGroupAdvice = Advice::factory()->create(['group_id' => $this->otherGroup->id]);

    // Make normal user member of main group
    $this->mainGroup->users()->attach($this->normalUser->id, ['is_admin' => false]);
    
    $context = new GroupContext($this->mainGroup, false, $this->normalUser);

    expect($context->hasAccessToAdvice($this->normalUser, $mainGroupAdvice))->toBeTrue()
        ->and($context->hasAccessToAdvice($this->normalUser, $otherGroupAdvice))->toBeFalse();
});

test('advisor access is group scoped', function() {
    $mainGroupAdvisor = User::factory()->create();
    $otherGroupAdvisor = User::factory()->create();

    $this->mainGroup->users()->attach($mainGroupAdvisor->id);
    $this->otherGroup->users()->attach($otherGroupAdvisor->id);

    // Make normal user member of main group
    $this->mainGroup->users()->attach($this->normalUser->id);
    
    $context = new GroupContext($this->mainGroup, false, $this->normalUser);

    expect($context->hasAccessToAdvisor($this->normalUser, $mainGroupAdvisor))->toBeTrue()
        ->and($context->hasAccessToAdvisor($this->normalUser, $otherGroupAdvisor))->toBeFalse();
});

test('admin of sub group cannot access parent groups', function() {
    // Make normal user admin of sub group only
    $this->subGroup->users()->attach($this->normalUser->id, ['is_admin' => true]);
    
    $context = new GroupContext($this->subGroup, false, $this->normalUser, true);

    expect($context->actsAsSystemAdmin($this->normalUser))->toBeFalse()
        ->and($context->hasAccessToGroup($this->normalUser, $this->mainGroup))->toBeFalse()
        ->and($context->hasAccessToGroup($this->normalUser, $this->subGroup))->toBeTrue()
        ->and($context->hasAccessToGroup($this->normalUser, $this->subSubGroup))->toBeTrue()
        ->and($context->actsAsGroupAdmin($this->normalUser, $this->mainGroup))->toBeFalse()
        ->and($context->actsAsGroupAdmin($this->normalUser, $this->subGroup))->toBeTrue()
        ->and($context->actsAsGroupAdmin($this->normalUser, $this->subSubGroup))->toBeTrue();
});

test('user with multiple group memberships has correct access', function() {
    // Make user member of multiple groups with different roles
    $this->mainGroup->users()->attach($this->normalUser->id, ['is_admin' => false]);
    $this->otherGroup->users()->attach($this->normalUser->id, ['is_admin' => true]);
    
    $context = new GroupContext($this->mainGroup, false, $this->normalUser);

    // Should have normal access to main group hierarchy
    expect($context->hasAccessToGroup($this->normalUser, $this->mainGroup))->toBeTrue()
        ->and($context->hasAccessToGroup($this->normalUser, $this->subGroup))->toBeTrue()
        ->and($context->hasAccessToGroup($this->normalUser, $this->subSubGroup))->toBeTrue()
        ->and($context->actsAsGroupAdmin($this->normalUser, $this->mainGroup))->toBeFalse()
        ->and($context->actsAsGroupAdmin($this->normalUser, $this->subGroup))->toBeFalse()
        ->and($context->actsAsGroupAdmin($this->normalUser, $this->subSubGroup))->toBeFalse();


    //without swichting the context, the normal user should not have access to the other group

    expect($context->hasAccessToGroup($this->normalUser, $this->otherGroup))->toBeFalse()
        ->and($context->hasAccessToGroup($this->normalUser, $this->otherSubGroup))->toBeFalse()
        ->and($context->actsAsGroupAdmin($this->normalUser, $this->otherGroup))->toBeFalse()
        ->and($context->actsAsGroupAdmin($this->normalUser, $this->otherSubGroup))->toBeFalse();

    $context = new GroupContext($this->otherGroup, false, $this->normalUser, true);

    // Should have admin access to other group hierarchy
    expect($context->hasAccessToGroup($this->normalUser, $this->otherGroup))->toBeTrue()
        ->and($context->hasAccessToGroup($this->normalUser, $this->otherSubGroup))->toBeTrue()
        ->and($context->actsAsGroupAdmin($this->normalUser, $this->otherGroup))->toBeTrue()
        ->and($context->actsAsGroupAdmin($this->normalUser, $this->otherSubGroup))->toBeTrue();

    //again without swichting the context, the normal user should not have access to the main group
    expect($context->hasAccessToGroup($this->normalUser, $this->mainGroup))->toBeFalse()
        ->and($context->hasAccessToGroup($this->normalUser, $this->subGroup))->toBeFalse()
        ->and($context->hasAccessToGroup($this->normalUser, $this->subSubGroup))->toBeFalse()
        ->and($context->actsAsGroupAdmin($this->normalUser, $this->mainGroup))->toBeFalse()
        ->and($context->actsAsGroupAdmin($this->normalUser, $this->subGroup))->toBeFalse()
        ->and($context->actsAsGroupAdmin($this->normalUser, $this->subSubGroup))->toBeFalse();
});

test('temporary admin access works independently for different groups', function() {
    // Make user member of both groups
    $this->mainGroup->users()->attach($this->normalUser->id, ['is_admin' => false]);
    $this->otherGroup->users()->attach($this->normalUser->id, ['is_admin' => false]);
    
    // Set up context with main group and admin rights
    $context1 = new GroupContext($this->mainGroup, false, $this->normalUser, true);

    expect($context1->actsAsGroupAdmin($this->normalUser, $this->mainGroup))->toBeTrue()
        ->and($context1->actsAsGroupAdmin($this->normalUser, $this->subGroup))->toBeTrue()
        ->and($context1->actsAsGroupAdmin($this->normalUser, $this->subSubGroup))->toBeTrue();

    // Switch to other group context without admin rights
    $context2 = new GroupContext($this->otherGroup, false, $this->normalUser, false);

    expect($context2->actsAsGroupAdmin($this->normalUser, $this->otherGroup))->toBeFalse()
        ->and($context2->actsAsGroupAdmin($this->normalUser, $this->otherSubGroup))->toBeFalse();
});

test('access is denied for null user', function() {
    $context = new GroupContext($this->mainGroup, false, null);

    $context->hasAccessToGroup($this->normalUser, $this->mainGroup);
})->throws(InvalidArgumentException::class);

test('system admin without current group still has full access', function() {
    $context = new GroupContext(null, true, $this->systemAdmin);
    
    expect($context->getCurrentGroup())->toBeNull()
        ->and($context->actsAsSystemAdmin($this->systemAdmin))->toBeTrue()
        ->and($context->hasAccessToGroup($this->systemAdmin, $this->mainGroup))->toBeTrue()
        ->and($context->hasAccessToGroup($this->systemAdmin, $this->subGroup))->toBeTrue()
        ->and($context->hasAccessToGroup($this->systemAdmin, $this->subSubGroup))->toBeTrue()
        ->and($context->actsAsGroupAdmin($this->systemAdmin, $this->mainGroup))->toBeTrue()
        ->and($context->actsAsGroupAdmin($this->systemAdmin, $this->subSubGroup))->toBeTrue();
});

test('user can access shared advisors across groups', function() {
    $sharedAdvisor = User::factory()->create();
    
    // Advisor is member of both groups
    $this->mainGroup->users()->attach($sharedAdvisor->id);
    $this->otherGroup->users()->attach($sharedAdvisor->id);
    
    // User is only member of main group
    $this->mainGroup->users()->attach($this->normalUser->id);
    
    $context = new GroupContext($this->mainGroup, false, $this->normalUser);
    
    expect($context->hasAccessToAdvisor($this->normalUser, $sharedAdvisor))->toBeTrue();
    
    // Even in global context (no specific group)
    $globalContext = new GroupContext(null, false, $this->normalUser);
    expect($globalContext->hasAccessToAdvisor($this->normalUser, $sharedAdvisor))->toBeTrue();
});

test('admin of parent group has access to child group advices', function() {
    // Create advice in sub-sub-group
    $deepAdvice = Advice::factory()->create(['group_id' => $this->subSubGroup->id]);
    
    // Make user admin of main group
    $this->mainGroup->users()->attach($this->normalUser->id, ['is_admin' => true]);
    
    $context = new GroupContext($this->mainGroup, false, $this->normalUser, true);
    
    expect($context->hasAccessToAdvice($this->normalUser, $deepAdvice))->toBeTrue()
        ->and($context->actsAsGroupAdmin($this->normalUser, $this->subSubGroup))->toBeTrue();
});