<?php

use App\Enums\FieldType;
use App\Models\Advice;
use App\Models\ChecklistEntry;
use App\Models\ChecklistEntryField;
use App\Models\FormDefinition;
use App\Models\FormField;
use App\Models\FormFieldOption;
use App\Models\Group;
use App\Models\User;
use App\Services\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->advisor = User::factory()->create();
    $this->group = Group::factory()->create();
    $this->group->users()->attach($this->advisor, ['is_admin' => true]);
    app(SessionService::class)->actAsGroup($this->group);

    $this->advice = Advice::factory()->create(['group_id' => $this->group->id, 'advisor_id' => $this->advisor->id]);
    $this->checklist = FormDefinition::factory()->checklist()->create(['group_id' => $this->group->id]);
});

test('advisor can add a checklist to their advice', function (): void {
    $this->actingAs($this->advisor)
        ->post(route('checklist-entries.store', $this->advice), ['form_definition_id' => $this->checklist->uuid])
        ->assertRedirect();

    expect(ChecklistEntry::count())->toBe(1);
    expect(ChecklistEntry::first()->form_definition_id)->toBe($this->checklist->id);
    expect(ChecklistEntry::first()->advice_id)->toBe($this->advice->id);
});

test('adding a checklist snapshots fields and options', function (): void {
    $checklist = FormDefinition::factory()->checklist()->create(['group_id' => $this->group->id]);
    $field = FormField::factory()->create([
        'form_definition_id' => $checklist->id,
        'type' => FieldType::SELECT,
        'label' => 'Heizung',
        'required' => true,
        'sort_order' => 0,
    ]);
    $field->options()->delete();
    $option = FormFieldOption::factory()->create([
        'form_field_id' => $field->id,
        'label' => 'Gas',
        'value' => 'gas',
        'sort_order' => 0,
    ]);

    $this->actingAs($this->advisor)
        ->post(route('checklist-entries.store', $this->advice), ['form_definition_id' => $checklist->uuid])
        ->assertRedirect();

    $entry = ChecklistEntry::firstOrFail();
    expect($entry->fields)->toHaveCount(1);

    $snapshotField = $entry->fields->first();
    expect($snapshotField->label)->toBe('Heizung');
    expect($snapshotField->type)->toBe(FieldType::SELECT);
    expect($snapshotField->required)->toBeTrue();
    expect($snapshotField->form_field_id)->toBe($field->id);

    expect($snapshotField->options)->toHaveCount(1);
    expect($snapshotField->options->first()->label)->toBe('Gas');
    expect($snapshotField->options->first()->value)->toBe('gas');
    expect($snapshotField->options->first()->form_field_option_id)->toBe($option->id);
});

test('cannot add same checklist twice to the same advice', function (): void {
    ChecklistEntry::factory()->create([
        'form_definition_id' => $this->checklist->id,
        'advice_id' => $this->advice->id,
    ]);

    $this->actingAs($this->advisor)
        ->post(route('checklist-entries.store', $this->advice), ['form_definition_id' => $this->checklist->uuid])
        ->assertInvalid('form_definition_id');
});

test('cannot add a form (non-checklist) as checklist', function (): void {
    $form = FormDefinition::factory()->create(['group_id' => $this->group->id]);

    $this->actingAs($this->advisor)
        ->post(route('checklist-entries.store', $this->advice), ['form_definition_id' => $form->uuid])
        ->assertInvalid('form_definition_id');
});

test('cannot add a checklist from a different initiative', function (): void {
    $otherGroup = Group::factory()->create();
    $otherChecklist = FormDefinition::factory()->checklist()->create(['group_id' => $otherGroup->id]);

    $this->actingAs($this->advisor)
        ->post(route('checklist-entries.store', $this->advice), ['form_definition_id' => $otherChecklist->uuid])
        ->assertInvalid('form_definition_id');
});

test('unauthorized user cannot add a checklist', function (): void {
    $other = User::factory()->create();

    $this->actingAs($other)
        ->post(route('checklist-entries.store', $this->advice), ['form_definition_id' => $this->checklist->uuid])
        ->assertForbidden();
});

test('advisor can update checklist entry field values', function (): void {
    $checklist = FormDefinition::factory()->checklist()->create(['group_id' => $this->group->id]);
    FormField::factory()->create([
        'form_definition_id' => $checklist->id,
        'type' => FieldType::TEXT,
        'label' => 'Anmerkung',
        'sort_order' => 0,
    ]);

    $this->actingAs($this->advisor)
        ->post(route('checklist-entries.store', $this->advice), ['form_definition_id' => $checklist->uuid])
        ->assertRedirect();

    $entry = ChecklistEntry::firstOrFail();
    $snapshotField = $entry->fields->first();

    $this->actingAs($this->advisor)
        ->put(route('checklist-entries.update', [$this->advice, $entry]), [
            'data' => [$snapshotField->uuid => 'Wert A'],
        ])
        ->assertRedirect();

    expect($snapshotField->fresh()->value)->toBe('Wert A');
});

test('updating ignores unknown field uuids', function (): void {
    $this->actingAs($this->advisor)
        ->post(route('checklist-entries.store', $this->advice), ['form_definition_id' => $this->checklist->uuid])
        ->assertRedirect();

    $entry = ChecklistEntry::firstOrFail();

    $this->actingAs($this->advisor)
        ->put(route('checklist-entries.update', [$this->advice, $entry]), [
            'data' => ['unknown-uuid' => 'irgendwas'],
        ])
        ->assertRedirect();

    expect(ChecklistEntryField::count())->toBe(0);
});

test('unauthorized user cannot update checklist entry', function (): void {
    $entry = ChecklistEntry::factory()->create([
        'form_definition_id' => $this->checklist->id,
        'advice_id' => $this->advice->id,
    ]);

    $other = User::factory()->create();

    $this->actingAs($other)
        ->put(route('checklist-entries.update', [$this->advice, $entry]), ['data' => []])
        ->assertForbidden();
});

test('snapshot stays stable when form definition fields are renamed', function (): void {
    $checklist = FormDefinition::factory()->checklist()->create(['group_id' => $this->group->id]);
    $field = FormField::factory()->create([
        'form_definition_id' => $checklist->id,
        'type' => FieldType::TEXT,
        'label' => 'Originallabel',
        'sort_order' => 0,
    ]);

    $this->actingAs($this->advisor)
        ->post(route('checklist-entries.store', $this->advice), ['form_definition_id' => $checklist->uuid]);

    $entry = ChecklistEntry::firstOrFail();
    expect($entry->fields->first()->label)->toBe('Originallabel');

    $field->update(['label' => 'Neues Label']);

    expect($entry->fresh()->fields->first()->label)->toBe('Originallabel');
});

test('snapshot stays intact when form field is deleted', function (): void {
    $checklist = FormDefinition::factory()->checklist()->create(['group_id' => $this->group->id]);
    $field = FormField::factory()->create([
        'form_definition_id' => $checklist->id,
        'type' => FieldType::TEXT,
        'label' => 'Wird gelöscht',
        'sort_order' => 0,
    ]);

    $this->actingAs($this->advisor)
        ->post(route('checklist-entries.store', $this->advice), ['form_definition_id' => $checklist->uuid]);

    $entry = ChecklistEntry::firstOrFail();
    $snapshotField = $entry->fields->first();
    $snapshotField->update(['value' => 'Schon befüllt']);

    $field->delete();

    $reloaded = $snapshotField->fresh();
    expect($reloaded->label)->toBe('Wird gelöscht');
    expect($reloaded->value)->toBe('Schon befüllt');
    expect($reloaded->form_field_id)->toBeNull();
});

test('advice show page contains checklist entries and available checklists', function (): void {
    $entry = ChecklistEntry::factory()->create([
        'form_definition_id' => $this->checklist->id,
        'advice_id' => $this->advice->id,
    ]);

    $anotherChecklist = FormDefinition::factory()->checklist()->create(['group_id' => $this->group->id]);

    $this->actingAs($this->advisor)
        ->get(route('advices.show', $this->advice))
        ->assertInertia(fn (Assert $page): Assert => $page
            ->component('Advice')
            ->has('checklistEntries', 1)
            ->where('checklistEntries.0.id', $entry->uuid)
            ->has('availableChecklists', 1)
            ->where('availableChecklists.0.id', $anotherChecklist->uuid)
        );
});

test('advice show page does not show forms as available checklists', function (): void {
    FormDefinition::factory()->create(['group_id' => $this->group->id]);

    $this->actingAs($this->advisor)
        ->get(route('advices.show', $this->advice))
        ->assertInertia(fn (Assert $page): Assert => $page
            ->component('Advice')
            ->has('availableChecklists', 1) // only the checklist from beforeEach
        );
});
