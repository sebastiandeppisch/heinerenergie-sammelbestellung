<?php

use App\Models\Group;
use App\Models\User;
use App\Services\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->admin()->create();
    $this->group = Group::factory()->create(['name' => 'Test Initiative']);
    $this->group->users()->attach($this->user, ['is_admin' => true]);
    app(SessionService::class)->actAsGroup($this->group, true);
    $this->actingAs($this->user);
});

test('dashboard editor renders without errors', function (): void {
    visit(route('dashboard'))
        ->assertNoSmoke()
        ->assertNoJavaScriptErrors();
});

test('admin can open and use the dashboard rich text editor', function (): void {
    visit(route('dashboard'))
        ->assertNoSmoke()
        ->assertNoJavaScriptErrors()
        ->click('[data-test="edit-dashboard-text"]')
        ->waitForText('Abbrechen')
        ->assertSee('Abbrechen')
        ->assertSee('Speichern');
});

test('admin can edit and save dashboard info', function (): void {
    visit(route('dashboard'))
        ->assertNoSmoke()
        ->click('[data-test="edit-dashboard-text"]')
        ->waitForText('Abbrechen')
        ->click('.ProseMirror')
        ->type('.ProseMirror', 'Willkommen beim Dashboard')
        ->click('Speichern')
        ->waitForEvent('networkidle')
        ->assertSee('Willkommen beim Dashboard')
        ->assertNoJavaScriptErrors();

    expect($this->group->fresh()->dashboard_info)->toContain('Willkommen beim Dashboard');
});

test('group email template editor renders without errors', function (): void {
    visit(route('groups.show', $this->group))
        ->assertNoSmoke()
        ->assertNoJavaScriptErrors();
});

test('group admin can open email tab and see rich text editor', function (): void {
    visit(route('groups.show', $this->group))
        ->assertNoSmoke()
        ->click('E-Mail')
        ->waitForText('E-Mail-Vorlage')
        ->assertSee('E-Mail-Vorlage für neue Beratungen')
        ->assertNoJavaScriptErrors();
});

test('group admin can save email template', function (): void {
    visit(route('groups.show', $this->group))
        ->assertNoSmoke()
        ->click('E-Mail')
        ->waitForText('E-Mail-Vorlage')
        ->click('.ProseMirror')
        ->type('.ProseMirror', 'Neue Beratung wurde erstellt')
        ->waitForText('Speichern')
        ->click('Speichern')
        ->waitForEvent('networkidle')
        ->assertNoJavaScriptErrors();

    expect($this->group->fresh()->new_advice_mail)->toContain('Neue Beratung wurde erstellt');
});
