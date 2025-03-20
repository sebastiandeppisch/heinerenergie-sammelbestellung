<?php

use App\Enums\AdviceStatusResult;
use App\Models\AdviceStatus;
use App\Models\Group;
use App\Models\User;
use App\Services\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create users with different roles
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->groupAdmin = User::factory()->create();
    $this->user = User::factory()->create();

    // Create group hierarchy
    $this->mainGroup = Group::factory()->create();
    $this->subGroup = Group::factory()->create(['parent_id' => $this->mainGroup->id]);

    // Assign group admin
    $this->groupAdmin->groups()->attach($this->subGroup->id, ['is_admin' => true]);

    $this->mainGroupStatus = AdviceStatus::create([
        'name' => 'Main Group Status',
        'result' => AdviceStatusResult::InProgress,
        'group_id' => $this->mainGroup->id,
    ]);

    $this->subGroupStatus = AdviceStatus::create([
        'name' => 'Sub Group Status',
        'result' => AdviceStatusResult::Completed,
        'group_id' => $this->subGroup->id,
    ]);

    Config::set('app.group_context', 'global');
});

afterEach(function () {
    // Reset session service
    app(SessionService::class)->clear();
});

it('lists all available statuses for a group', function () {
    $response = $this->actingAs($this->groupAdmin)
        ->getJson("/api/groups/{$this->subGroup->id}/advicestatus");

    $response->assertOk()
        ->assertJsonCount(2)
        ->assertJsonFragment(['name' => 'Main Group Status'])
        ->assertJsonFragment(['name' => 'Sub Group Status']);

    // All statuses should be visible by default
    $statuses = $response->json();
    foreach ($statuses as $status) {
        expect($status['visible_in_group'])->toBeTrue();
    }
});

it('can create a new status for a group', function () {
    $response = $this->actingAs($this->groupAdmin)
        ->postJson("/api/groups/{$this->subGroup->id}/advicestatus", [
            'name' => 'New Status',
            'result' => AdviceStatusResult::New->value,
        ]);

    $response->assertCreated()
        ->assertJsonFragment([
            'name' => 'New Status',
            'group_id' => $this->subGroup->id,
            'visible_in_group' => true,
        ]);

    $this->assertDatabaseHas('advice_status', [
        'name' => 'New Status',
        'group_id' => $this->subGroup->id,
    ]);
});

it('can update visibility of parent status in own group', function () {
    $response = $this->actingAs($this->groupAdmin)
        ->putJson("/api/groups/{$this->subGroup->id}/advicestatus/{$this->mainGroupStatus->id}", [
            'visible_in_group' => false,
        ]);

    $response->assertOk()
        ->assertJsonFragment([
            'id' => $this->mainGroupStatus->id,
            'visible_in_group' => false,
        ]);

    $this->assertDatabaseHas('advice_status_group', [
        'group_id' => $this->subGroup->id,
        'advice_status_id' => $this->mainGroupStatus->id,
        'visible_in_group' => false,
    ]);
});

it('cannot update visibility of parent status in parent group', function () {
    $response = $this->actingAs($this->groupAdmin)
        ->putJson("/api/groups/{$this->mainGroup->id}/advicestatus/{$this->mainGroupStatus->id}", [
            'visible_in_group' => false,
        ]);

    $response->assertForbidden();
});

it('can update own group status', function () {
    $response = $this->actingAs($this->groupAdmin)
        ->putJson("/api/groups/{$this->subGroup->id}/advicestatus/{$this->subGroupStatus->id}", [
            'name' => 'Updated Status',
            'result' => AdviceStatusResult::Unsuccessfully->value,
        ]);

    $response->assertOk()
        ->assertJsonFragment([
            'name' => 'Updated Status',
            'result' => AdviceStatusResult::Unsuccessfully->value,
        ]);

    $this->assertDatabaseHas('advice_status', [
        'id' => $this->subGroupStatus->id,
        'name' => 'Updated Status',
        'result' => AdviceStatusResult::Unsuccessfully->value,
    ]);
});

it('cannot update parent group status fields', function () {
    $originalName = $this->mainGroupStatus->name;

    $response = $this->actingAs($this->groupAdmin)
        ->putJson("/api/groups/{$this->subGroup->id}/advicestatus/{$this->mainGroupStatus->id}", [
            'name' => 'Try to update parent',
            'result' => AdviceStatusResult::Unsuccessfully->value,
        ]);

    $response->assertForbidden();
});

it('can delete own group status', function () {
    $response = $this->actingAs($this->groupAdmin)
        ->deleteJson("/api/groups/{$this->subGroup->id}/advicestatus/{$this->subGroupStatus->id}");

    $response->assertNoContent();

    $this->assertNull(AdviceStatus::find($this->subGroupStatus->id));
});

it('cannot delete parent group status', function () {
    $response = $this->actingAs($this->groupAdmin)
        ->deleteJson("/api/groups/{$this->subGroup->id}/advicestatus/{$this->mainGroupStatus->id}");

    $response->assertForbidden();
});

test('regular users cannot access status management', function () {
    $this->actingAs($this->user)
        ->getJson("/api/groups/{$this->subGroup->id}/advicestatus")
        ->assertForbidden();

    $this->actingAs($this->user)
        ->postJson("/api/groups/{$this->subGroup->id}/advicestatus", [
            'name' => 'New Status',
            'result' => AdviceStatusResult::New->value,
        ])
        ->assertForbidden();
});

test('group admin cannot manage other groups statuses', function () {
    $otherGroup = Group::factory()->create();

    $this->actingAs($this->groupAdmin)
        ->getJson("/api/groups/{$otherGroup->id}/advicestatus")
        ->assertForbidden();

    $this->actingAs($this->groupAdmin)
        ->postJson("/api/groups/{$otherGroup->id}/advicestatus", [
            'name' => 'New Status',
            'result' => AdviceStatusResult::New->value,
        ])
        ->assertForbidden();
});

test('system admin can manage any group status', function () {
    $this->actingAs($this->admin)
        ->getJson("/api/groups/{$this->subGroup->id}/advicestatus")
        ->assertOk();

    $response = $this->actingAs($this->admin)
        ->postJson("/api/groups/{$this->subGroup->id}/advicestatus", [
            'name' => 'Admin Created Status',
            'result' => AdviceStatusResult::New->value,
        ]);

    $response->assertCreated()
        ->assertJsonFragment([
            'name' => 'Admin Created Status',
        ]);
});

it('can toggle visibility of parent status in child group', function () {
    // We're using the existing group hierarchy (mainGroup is parent of subGroup)
    // The status belongs to the mainGroup (parent)

    // First, check the initial state via the API
    $initialResponse = $this->actingAs($this->groupAdmin)
        ->getJson("/api/groups/{$this->subGroup->id}/advicestatus");

    $initialResponse->assertOk();
    $statusesData = collect($initialResponse->json());
    $mainGroupStatus = $statusesData->firstWhere('id', $this->mainGroupStatus->id);
    expect($mainGroupStatus['visible_in_group'])->toBeTrue();

    // Now toggle the status to invisible
    $updateResponse = $this->actingAs($this->groupAdmin)
        ->putJson("/api/groups/{$this->subGroup->id}/advicestatus/{$this->mainGroupStatus->id}", [
            'visible_in_group' => false,
        ]);

    $updateResponse->assertOk()
        ->assertJsonFragment([
            'id' => $this->mainGroupStatus->id,
            'visible_in_group' => false,
        ]);

    // Verify that the data is correctly stored in the database
    $this->assertDatabaseHas('advice_status_group', [
        'group_id' => $this->subGroup->id,
        'advice_status_id' => $this->mainGroupStatus->id,
        'visible_in_group' => false,
    ]);

    // Now check with a GET request if the API returns the updated data
    $getAfterUpdateResponse = $this->actingAs($this->groupAdmin)
        ->getJson("/api/groups/{$this->subGroup->id}/advicestatus");

    $getAfterUpdateResponse->assertOk();
    $updatedStatusesData = collect($getAfterUpdateResponse->json());
    $updatedMainGroupStatus = $updatedStatusesData->firstWhere('id', $this->mainGroupStatus->id);
    expect($updatedMainGroupStatus['visible_in_group'])->toBeFalse();

    // And toggle back to visible
    $revertResponse = $this->actingAs($this->groupAdmin)
        ->putJson("/api/groups/{$this->subGroup->id}/advicestatus/{$this->mainGroupStatus->id}", [
            'visible_in_group' => true,
        ]);

    $revertResponse->assertOk()
        ->assertJsonFragment([
            'id' => $this->mainGroupStatus->id,
            'visible_in_group' => true,
        ]);

    // Verify that the data is correctly stored in the database
    $this->assertDatabaseHas('advice_status_group', [
        'group_id' => $this->subGroup->id,
        'advice_status_id' => $this->mainGroupStatus->id,
        'visible_in_group' => true,
    ]);

    // Finally, check with a GET request if the API returns the updated data again
    $finalResponse = $this->actingAs($this->groupAdmin)
        ->getJson("/api/groups/{$this->subGroup->id}/advicestatus");

    $finalResponse->assertOk();
    $finalStatusesData = collect($finalResponse->json());
    $finalMainGroupStatus = $finalStatusesData->firstWhere('id', $this->mainGroupStatus->id);
    expect($finalMainGroupStatus['visible_in_group'])->toBeTrue();
});

it('can toggle visibility of own status in own group', function () {
    // We're using the existing group hierarchy
    // The status belongs to the own subgroup (subGroup)

    // First, check the initial state via the API
    $initialResponse = $this->actingAs($this->groupAdmin)
        ->getJson("/api/groups/{$this->subGroup->id}/advicestatus");

    $initialResponse->assertOk();
    $statusesData = collect($initialResponse->json());
    $subGroupStatus = $statusesData->firstWhere('id', $this->subGroupStatus->id);
    expect($subGroupStatus['visible_in_group'])->toBeTrue();

    // Now toggle the status to invisible (own initiative)
    $updateResponse = $this->actingAs($this->groupAdmin)
        ->putJson("/api/groups/{$this->subGroup->id}/advicestatus/{$this->subGroupStatus->id}", [
            'visible_in_group' => false,
        ]);

    $updateResponse->assertOk()
        ->assertJsonFragment([
            'id' => $this->subGroupStatus->id,
            'visible_in_group' => false,
        ]);

    // Verify that the data is correctly stored in the database
    $this->assertDatabaseHas('advice_status_group', [
        'group_id' => $this->subGroup->id,
        'advice_status_id' => $this->subGroupStatus->id,
        'visible_in_group' => false,
    ]);

    // Now check with a GET request if the API returns the updated data
    $getAfterUpdateResponse = $this->actingAs($this->groupAdmin)
        ->getJson("/api/groups/{$this->subGroup->id}/advicestatus");

    $getAfterUpdateResponse->assertOk();
    $updatedStatusesData = collect($getAfterUpdateResponse->json());
    $updatedSubGroupStatus = $updatedStatusesData->firstWhere('id', $this->subGroupStatus->id);
    expect($updatedSubGroupStatus['visible_in_group'])->toBeFalse();

    // And toggle back to visible
    $revertResponse = $this->actingAs($this->groupAdmin)
        ->putJson("/api/groups/{$this->subGroup->id}/advicestatus/{$this->subGroupStatus->id}", [
            'visible_in_group' => true,
        ]);

    $revertResponse->assertOk()
        ->assertJsonFragment([
            'id' => $this->subGroupStatus->id,
            'visible_in_group' => true,
        ]);

    // Verify that the data is correctly stored in the database
    $this->assertDatabaseHas('advice_status_group', [
        'group_id' => $this->subGroup->id,
        'advice_status_id' => $this->subGroupStatus->id,
        'visible_in_group' => true,
    ]);

    // Finally, check with a GET request if the API returns the updated data again
    $finalResponse = $this->actingAs($this->groupAdmin)
        ->getJson("/api/groups/{$this->subGroup->id}/advicestatus");

    $finalResponse->assertOk();
    $finalStatusesData = collect($finalResponse->json());
    $finalSubGroupStatus = $finalStatusesData->firstWhere('id', $this->subGroupStatus->id);
    expect($finalSubGroupStatus['visible_in_group'])->toBeTrue();
});
