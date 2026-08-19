<?php

use App\Models\Group;
use App\Models\User;
use App\Services\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->admin()->create(['first_name' => 'Admin', 'last_name' => 'User']);
    $this->group = Group::factory()->create(['name' => 'Test Initiative']);
    $this->group->users()->attach($this->user, ['is_admin' => true]);

    $this->member = User::factory()->create(['first_name' => 'John', 'last_name' => 'Doe', 'is_active' => true]);
    $this->group->users()->attach($this->member->id, ['is_admin' => false]);

    app(SessionService::class)->actAsGroup($this->group, true);
    $this->actingAs($this->user);
});

test('group users table renders columns and cell formatting', function () {
    visit(route('groups.show', $this->group).'#tab=1')
        ->assertNoSmoke()
        ->assertSee('Vorname')
        ->assertSee('Nachname')
        ->assertSee('Admin')
        ->assertSee('Bearbeiten')
        // first/last name split into separate columns
        ->assertSee('John')
        ->assertSee('Doe')
        // is_admin renders as a Ja/Nein pill, not "true"/"false"
        ->assertSee('Ja')
        ->assertSee('Nein')
        ->assertDontSee('true')
        ->assertDontSee('false')
        ->assertNoJavaScriptErrors();
});

test('editing a user toggles admin via dialog', function () {
    visit(route('groups.show', $this->group).'#tab=1')
        ->assertNoSmoke()
        ->click('[data-test="edit-user-'.$this->member->uuid.'"]')
        ->assertSee('Admin-Rechte')
        ->click('#admin-checkbox')
        ->click('[data-test="save-edit"]')
        ->assertNoJavaScriptErrors();

    expect($this->group->fresh()->admins()->where('users.id', $this->member->id)->exists())
        ->toBeTrue();
});

test('removing a user asks for confirmation and detaches', function () {
    visit(route('groups.show', $this->group).'#tab=1')
        ->assertNoSmoke()
        ->click('[data-test="remove-user-'.$this->member->uuid.'"]')
        ->assertSee('Berater:in entfernen?')
        ->click('[data-test="confirm-remove"]')
        ->assertNoJavaScriptErrors();

    expect($this->group->fresh()->users()->where('users.id', $this->member->id)->exists())
        ->toBeFalse();
});

test('adding a user via the dialog attaches them to the group', function () {
    $candidate = User::factory()->create(['first_name' => 'Jane', 'last_name' => 'Smith', 'is_active' => true]);

    visit(route('groups.show', $this->group).'#tab=1')
        ->assertNoSmoke()
        ->click('[data-test="add-user"]')
        ->assertSee('Person auswählen')
        ->click('Person auswählen...')
        ->click('Jane Smith')
        ->click('[data-test="confirm-add"]')
        ->assertNoJavaScriptErrors();

    expect($this->group->fresh()->users()->where('users.id', $candidate->id)->exists())
        ->toBeTrue();
});
