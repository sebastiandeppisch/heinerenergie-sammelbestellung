<?php

namespace Tests\Feature;

use App\Models\Advice;
use App\Models\Group;
use App\Models\User;
use App\Services\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\put;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->group = Group::factory()->create();
    $this->otherGroup = Group::factory()->create();
    $this->advice = Advice::factory()->create(['group_id' => $this->group->id]);
    $this->otherAdvice = Advice::factory()->create(['group_id' => $this->otherGroup->id]);

    // Add user to group
    $this->group->users()->attach($this->user);

    $this->systemAdmin = User::factory()->create(['is_admin' => true]);
});

test('user can access own group resources', function () {

    $this->withoutExceptionHandling();

    // Switch to group context
    actingAs($this->user)
        ->post("/actAsGroup/{$this->group->uuid}");

    // Should be able to access group's advice
    $response = get("/advices/{$this->advice->uuid}");
    expect($response->status())->toBe(200);

    // But not other group's advice
    $response = get("/advices/{$this->otherAdvice->uuid}");

    $response->assertSessionHasErrors();
    $response->assertRedirect('/advices');
})->todo('this needs to be better defined: The user should only see the advice data protected and when is it not assigned to another advisor');

test('group admin has elevated privileges', function () {
    actingAs($this->user);

    // should not be able to modify group settings
    put(route('groups.update', $this->group), [
        'name' => 'Updated Name',
    ])->assertStatus(403);

    $this->group->users()->updateExistingPivot($this->user->id, ['is_admin' => true]);

    // Switch to group context as admin
    post("/actAsGroup/{$this->group->uuid}", ['asAdmin' => true])->assertSessionHasNoErrors();

    // Should be able to modify group settings
    $response = actingAs($this->user)->put("/groups/{$this->group->uuid}", [
        'name' => 'Updated Name',
    ]);

    expect($response->status())->not()->toBe(403);
});

test('normal user cannot modify group settings', function () {
    // Switch to group context without admin
    actingAs($this->user)
        ->post("/actAsGroup/{$this->group->uuid}");

    // Should not be able to modify group settings
    $response = put("/groups/{$this->group->uuid}", [
        'name' => 'Updated Name',
    ]);
    expect($response->status())->toBe(403);
});

test('system admin can access and modify any group', function () {
    // Become system admin
    actingAs($this->systemAdmin)
        ->post('/actAsSystemAdmin');

    // Should be able to access any group's resources
    $response = get("/advices/{$this->otherAdvice->uuid}");
    expect($response->status())->toBe(200);

    // And modify any group
    $response = put("/groups/{$this->otherGroup->uuid}", [
        'name' => 'Updated Name',
    ]);
    expect($response->status())->not()->toBe(403);
});

test('context switching changes access rights', function () {
    // First as normal group member
    actingAs($this->user)
        ->post("/actAsGroup/{$this->group->uuid}", ['asAdmin' => false])->assertSessionHasNoErrors();

    $response = put("/groups/{$this->group->uuid}", [
        'name' => 'Updated Name',
    ]);

    expect($response->status())->toBe(403);

    // Make user admin of the group
    $this->group->users()->updateExistingPivot($this->user->id, ['is_admin' => true]);

    // Then as group admin
    actingAs($this->user)
        ->post("/actAsGroup/{$this->group->uuid}", ['asAdmin' => true])->assertSessionHasNoErrors();
    $response = put("/groups/{$this->group->uuid}", [
        'name' => 'Updated Name',
    ]);
    expect($response->status())->not()->toBe(403);

    // Then as system admin
    $this->user->is_admin = true;
    $this->user->save();

    actingAs($this->user)
        ->post('/actAsSystemAdmin')->assertSessionHasNoErrors();

    $response = put("/groups/{$this->otherGroup->uuid}", [
        'name' => 'Updated Name',
    ]);
    expect($response->status())->not()->toBe(403);

    // Finally back to normal group member
    $this->group->users()->updateExistingPivot($this->user->id, ['is_admin' => false]);

    actingAs($this->user)
        ->post("/actAsGroup/{$this->group->uuid}", ['asAdmin' => false])->assertSessionHasNoErrors();

    $response = put("/groups/{$this->group->uuid}", [
        'name' => 'Updated Name',
    ]);
    expect($response->status())->toBe(403);
});

test('unauthenticated user has no access', function () {
    $response = get("/advices/{$this->advice->uuid}");
    expect($response->status())->toBe(302); // Redirects to login
});

test('user can switch to own group', function () {
    $response = actingAs($this->user)
        ->post("/actAsGroup/{$this->group->uuid}");

    expect($response->assertSessionHasNoErrors());

    expect(session('actAsGroupId'))->toBe($this->group->id)
        ->and(session('actAsGroupAdmin'))->toBeFalse()
        ->and(session('actAsSystemAdmin'))->toBeNull();
});

test('user can switch to own group as admin when they is an group admin', function () {
    // Make user admin of the group
    $this->group->users()->updateExistingPivot($this->user->id, ['is_admin' => true]);

    $response = actingAs($this->user)
        ->post("/actAsGroup/{$this->group->uuid}", ['asAdmin' => true]);

    expect($response->assertSessionHasNoErrors());

    expect(session('actAsGroupId'))->toBe($this->group->id)
        ->and(session('actAsGroupAdmin'))->toBeTrue()
        ->and(session('actAsSystemAdmin'))->toBeNull();
});

test('non-admin user cannot switch to group as admin', function () {
    // Ensure user is not admin of the group
    $this->group->users()->updateExistingPivot($this->user->id, ['is_admin' => false]);

    $response = actingAs($this->user)
        ->post("/actAsGroup/{$this->group->uuid}", ['asAdmin' => true]);

    expect($response->assertSessionHasErrors())
        ->and(session('actAsGroupId'))->toBeNull()
        ->and(session('actAsGroupAdmin'))->toBeNull()
        ->and(session('actAsSystemAdmin'))->toBeNull();
});

test('user cannot switch to other group', function () {
    $response = actingAs($this->user)
        ->post("/actAsGroup/{$this->otherGroup->uuid}");

    expect($response->assertSessionHasErrors())
        ->and(session('actAsGroupId'))->toBeNull();
});

test('system admin can switch to any group', function () {
    // First become system admin
    actingAs($this->systemAdmin)
        ->post('/actAsSystemAdmin')->assertSessionHasNoErrors();

    $response = actingAs($this->systemAdmin)
        ->post("/actAsGroup/{$this->otherGroup->uuid}");

    expect($response->assertSessionHasNoErrors())
        ->and(session('actAsGroupId'))->toBe($this->otherGroup->id)
        ->and(session('actAsGroupAdmin'))->toBeFalse()
        ->and(session('actAsSystemAdmin'))->toBeNull();
});

test('switching context clears previous context', function () {
    // Make user a system admin
    $this->user->is_admin = true;
    $this->user->save();

    // First act as group admin
    actingAs($this->user)
        ->post("/actAsGroup/{$this->group->uuid}", ['asAdmin' => true])->assertSessionHasNoErrors();

    expect(session('actAsGroupId'))->toBe($this->group->id)
        ->and(session('actAsGroupAdmin'))->toBeTrue()
        ->and(session('actAsSystemAdmin'))->toBeNull();

    // Then switch to system admin
    $response = actingAs($this->user)
        ->post('/actAsSystemAdmin')->assertSessionHasNoErrors();

    expect($response->assertSessionHasNoErrors())
        ->and(session('actAsGroupId'))->toBeNull()
        ->and(session('actAsGroupAdmin'))->toBeNull()
        ->and(session('actAsSystemAdmin'))->toBeTrue();

    // Then switch back to group (without admin)
    $response = actingAs($this->user)
        ->post("/actAsGroup/{$this->group->uuid}")->assertSessionHasNoErrors();

    expect($response->assertSessionHasNoErrors())
        ->and(session('actAsGroupId'))->toBe($this->group->id)
        ->and(session('actAsGroupAdmin'))->toBeFalse()
        ->and(session('actAsSystemAdmin'))->toBeNull();
});

test('unauthenticated user cannot switch groups', function () {
    $response = post("/actAsGroup/{$this->group->uuid}");

    expect($response->assertRedirect(route('login')))
        ->and(session('actAsGroupId'))->toBeNull();
});

test('unauthenticated user cannot become system admin', function () {
    $response = post('/actAsSystemAdmin');

    expect($response->assertRedirect(route('login')))
        ->and(session('actAsSystemAdmin'))->toBeNull();
});

test('switching to invalid group returns 404', function () {
    $response = actingAs($this->user)
        ->post('/actAsGroup/99999');

    expect($response->assertNotFound())
        ->and(session('actAsGroupId'))->toBeNull();
});

test('clearing session removes all context information', function () {
    // Set up initial state with group admin privileges
    actingAs($this->user)
        ->post("/actAsGroup/{$this->group->uuid}", ['asAdmin' => true]);

    // Clear session
    app(SessionService::class)->clear();

    // All context information should be gone
    expect(session('actAsGroupId'))->toBeNull()
        ->and(session('actAsGroupAdmin'))->toBeNull()
        ->and(session('actAsSystemAdmin'))->toBeNull();
});
