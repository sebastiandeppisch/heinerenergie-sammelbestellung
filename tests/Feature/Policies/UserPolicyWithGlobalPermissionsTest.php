<?php

use App\Models\Advice;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

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

    config()->set('app.group_context', 'global');
});

test('advisor access is group scoped', function () {
    $mainGroupAdvisor = User::factory()->create();
    $otherGroupAdvisor = User::factory()->create();

    $this->mainGroup->users()->attach($mainGroupAdvisor->id);
    $this->otherGroup->users()->attach($otherGroupAdvisor->id);

    // Make normal user member of main group
    $this->mainGroup->users()->attach($this->normalUser->id);

    expect($mainGroupAdvisor->can('viewAny', Group::class))->toBeTrue()
        ->and($mainGroupAdvisor->can('view', $this->mainGroup))->toBeTrue()
        ->and($mainGroupAdvisor->can('view', $this->subGroup))->toBeTrue()
        ->and($mainGroupAdvisor->can('view', $this->otherGroup))->toBeFalse()
        ->and($mainGroupAdvisor->can('view', $this->otherSubGroup))->toBeFalse();
});

test('user can access shared advisors across groups', function () {
    $sharedAdvisor = User::factory()->create();

    $advice = Advice::factory()->create([
        'group_id' => $this->mainGroup->id,
        'advisor_id' => $this->normalUser->id,
    ]);

    expect($this->normalUser->can('view', $sharedAdvisor))->toBeFalse('The user should not be able to view the shared advisor before sharing');
    $advice->shares()->attach($sharedAdvisor->id);

    expect($this->normalUser->can('view', $sharedAdvisor))->toBeTrue('The user should be able to view the shared advisor after sharing');
})->todo('sharing between groups is not yet completely defined');


test('user can see colleagues in same group', function () {
    $colleague = User::factory()->create();

    $group = Group::factory()->create();

    $group->users()->attach($colleague->id);
    $group->users()->attach($this->normalUser->id);

    expect($this->normalUser->can('view', $colleague))->toBeTrue();
});
