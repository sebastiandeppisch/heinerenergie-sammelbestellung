<?php

namespace Tests\Feature\Policies;

use App\Context\GlobalGroupContext;
use App\Context\GroupContextContract;
use App\Models\Advice;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create a global admin
    $this->globalAdmin = User::factory()->create(['is_admin' => true]);

    // Create two initiatives
    $this->initiative = Group::factory()->create();
    $this->otherInitiative = Group::factory()->create();

    // Create an initiative admin
    $this->initiativeAdmin = User::factory()->create();
    $this->initiative->users()->attach($this->initiativeAdmin, ['is_admin' => true]);

    // Create an advisor in the initiative
    $this->advisor = User::factory()->create();
    $this->initiative->users()->attach($this->advisor, ['is_admin' => false]);

    // Create an advisor in another initiative
    $this->otherInitiativeAdvisor = User::factory()->create();
    $this->otherInitiative->users()->attach($this->otherInitiativeAdvisor, ['is_admin' => false]);

    // Create an advice in the initiative
    $this->advice = Advice::factory()->create([
        'group_id' => $this->initiative->id,
        'advisor_id' => $this->advisor->id,
    ]);
    app()->bind(GroupContextContract::class, GlobalGroupContext::class);
});

test('global admin can view any advice', function () {

    expect($this->globalAdmin->can('view', $this->advice))->toBeTrue();
});

test('initiative admin can view advice in their initiative', function () {
    expect($this->initiativeAdmin->can('view', $this->advice))->toBeTrue();
});

test('initiative admin cannot view advice in other initiative', function () {
    $otherAdvice = Advice::factory()->create([
        'group_id' => $this->otherInitiative->id,
    ]);

    expect($this->initiativeAdmin->can('view', $otherAdvice))->toBeFalse();
});

test('advisor can view advice in their initiative', function () {
    expect($this->advisor->can('view', $this->advice))->toBeTrue();
});

test('advisor cannot view advice in other initiative', function () {
    expect($this->otherInitiativeAdvisor->can('view', $this->advice))->toBeFalse();
});

test('global admin can update any advice', function () {
    Auth::login($this->globalAdmin);
    session()->put('isAdmin', true);

    expect($this->globalAdmin->can('update', $this->advice))->toBeTrue();
});

test('initiative admin can update advice in their initiative', function () {
    expect($this->initiativeAdmin->can('update', $this->advice))->toBeTrue();
});

test('initiative admin cannot update advice in other initiative', function () {
    $otherAdvice = Advice::factory()->create([
        'group_id' => $this->otherInitiative->id,
    ]);

    expect($this->initiativeAdmin->can('update', $otherAdvice))->toBeFalse();
});

test('advisor can update their own advice', function () {
    expect($this->advisor->can('update', $this->advice))->toBeTrue();
});

test('advisor cannot update other advisors advice in same initiative', function () {
    $otherAdvisor = User::factory()->create();
    $this->initiative->users()->attach($otherAdvisor, ['is_admin' => false]);

    $otherAdvice = Advice::factory()->create([
        'group_id' => $this->initiative->id,
        'advisor_id' => $otherAdvisor->id,
    ]);

    expect($this->advisor->can('update', $otherAdvice))->toBeFalse();
});
