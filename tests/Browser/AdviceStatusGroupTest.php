<?php

use App\Enums\AdviceStatusResult;
use App\Models\AdviceStatus;
use App\Models\Group;
use App\Models\User;
use App\Services\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->admin()->create();
    $this->group = Group::factory()->create(['name' => 'Test Initiative']);
    $this->group->users()->attach($this->user, ['is_admin' => true]);
    app(SessionService::class)->actAsGroup($this->group, true);
    $this->actingAs($this->user);
});

test('advice status table renders without errors', function () {
    visit(route('groups.show', $this->group))
        ->assertNoSmoke()
        ->click('Beratungszustände')
        ->waitForText('Hinzufügen')
        ->assertNoJavaScriptErrors();
});

test('admin can add new advice status', function () {
    visit(route('groups.show', $this->group))
        ->assertNoSmoke()
        ->click('Beratungszustände')
        ->waitForText('Hinzufügen')
        ->click('Hinzufügen')
        ->waitForText('Beratungszustand erstellen')
        ->fill('input[placeholder="z.B. Ausstehend"]', 'Neuer Status')
        ->click('Ergebnis wählen')
        ->waitForText('Neu')
        ->click('Neu')
        ->click('[data-test="save-status"]')
        ->waitForEvent('networkidle')
        ->assertSee('Neuer Status')
        ->assertNoJavaScriptErrors();

    expect(AdviceStatus::where('name', 'Neuer Status')->exists())->toBeTrue();
});

test('admin can edit advice status name', function () {
    AdviceStatus::create([
        'name' => 'Alter Status',
        'result' => AdviceStatusResult::New,
        'group_id' => $this->group->id,
    ]);

    visit(route('groups.show', $this->group))
        ->assertNoSmoke()
        ->click('Beratungszustände')
        ->waitForText('Alter Status')
        ->click('[data-test="edit-status"] button')
        ->waitForText('Beratungszustand bearbeiten')
        ->fill('input[placeholder="z.B. Ausstehend"]', 'Geänderter Status')
        ->click('[data-test="save-status"]')
        ->waitForEvent('networkidle')
        ->assertSee('Geänderter Status')
        ->assertNoJavaScriptErrors();

    expect(AdviceStatus::where('name', 'Geänderter Status')->exists())->toBeTrue();
});

test('admin can delete advice status', function () {
    AdviceStatus::create([
        'name' => 'Zu löschender Status',
        'result' => AdviceStatusResult::New,
        'group_id' => $this->group->id,
    ]);

    visit(route('groups.show', $this->group))
        ->assertNoSmoke()
        ->click('Beratungszustände')
        ->waitForText('Zu löschender Status')
        ->click('[data-test="delete-status"] button')
        ->waitForText('Beratungszustand löschen?')
        ->click('[data-test="confirm-delete"]')
        ->waitForEvent('networkidle')
        ->assertDontSee('Zu löschender Status')
        ->assertNoJavaScriptErrors();

    expect(AdviceStatus::where('name', 'Zu löschender Status')->exists())->toBeFalse();
});

test('admin can toggle status visibility and save', function () {
    $status = AdviceStatus::create([
        'name' => 'Sichtbarer Status',
        'result' => AdviceStatusResult::New,
        'group_id' => $this->group->id,
    ]);

    // Visible by default (no explicit pivot entry).
    expect($status->isVisibleInGroup($this->group))->toBeTrue();

    visit(route('groups.show', $this->group))
        ->assertNoSmoke()
        ->click('Beratungszustände')
        ->waitForText('Verwendete Beratungszustände')
        ->click('[data-slot="switch"]')
        ->click('[data-test="save-visibility"]')
        ->waitForEvent('networkidle')
        ->assertNoJavaScriptErrors();

    expect($status->fresh()->isVisibleInGroup($this->group))->toBeFalse();
});
