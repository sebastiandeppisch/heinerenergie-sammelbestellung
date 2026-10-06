<?php

use App\Enums\FieldType;
use App\Models\FormDefinition;
use App\Models\FormDefinitionToAdvice;
use App\Models\FormField;
use App\Models\Group;
use App\Models\User;
use App\Services\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->group = Group::factory()->create(['name' => 'Test Initiative']);
    $this->group->users()->attach($this->user, ['is_admin' => true]);
    app(SessionService::class)->actAsGroup($this->group, true);
    $this->actingAs($this->user);
});

test('the address field of an advice form cannot be made optional', function (): void {
    $creator = FormDefinitionToAdvice::factory()->create();
    $formDefinition = $creator->formDefinition;
    $formDefinition->update(['group_id' => $this->group->id]);

    $page = visit(route('form-definitions.edit', $formDefinition));

    $page->click('Address')
        ->assertSee('Feldeigenschaften')
        ->assertSee('kannst du es nicht optional machen')
        ->assertDisabled('#field_required')
        ->assertNoJavaScriptErrors();
});

test('another field of an advice form can still be made optional', function (): void {
    $creator = FormDefinitionToAdvice::factory()->create();
    $formDefinition = $creator->formDefinition;
    $formDefinition->update(['group_id' => $this->group->id]);

    $page = visit(route('form-definitions.edit', $formDefinition));

    $page->click('Phone Number')
        ->assertSee('Feldeigenschaften')
        ->assertDontSee('kannst du es nicht optional machen')
        ->assertEnabled('#field_required')
        ->assertNoJavaScriptErrors();
});

test('an advice address field that was still optional is shown as required again', function (): void {
    $creator = FormDefinitionToAdvice::factory()->create();
    $formDefinition = $creator->formDefinition;
    $formDefinition->update(['group_id' => $this->group->id]);
    $creator->addressField->update(['required' => false]);

    $page = visit(route('form-definitions.edit', $formDefinition));

    $page->click('Address')
        ->assertSee('Feldeigenschaften')
        ->assertAriaAttribute('#field_required', 'checked', 'true')
        ->assertDisabled('#field_required')
        ->assertNoJavaScriptErrors();
});

test('an address field offers neither a placeholder nor a default value', function (): void {
    $creator = FormDefinitionToAdvice::factory()->create();
    $formDefinition = $creator->formDefinition;
    $formDefinition->update(['group_id' => $this->group->id]);

    $page = visit(route('form-definitions.edit', $formDefinition));

    $page->click('Address')
        ->assertSee('Feldeigenschaften')
        ->assertDontSee('Platzhaltertext')
        ->assertDontSee('Standardwert')
        ->assertNoJavaScriptErrors();
});

test('a text field still offers a placeholder and a default value', function (): void {
    $creator = FormDefinitionToAdvice::factory()->create();
    $formDefinition = $creator->formDefinition;
    $formDefinition->update(['group_id' => $this->group->id]);

    $page = visit(route('form-definitions.edit', $formDefinition));

    $page->click('First Name')
        ->assertSee('Feldeigenschaften')
        ->assertSee('Platzhaltertext')
        ->assertSee('Standardwert')
        ->assertNoJavaScriptErrors();
});

test('the canvas previews an address field with its disabled inputs', function (): void {
    $creator = FormDefinitionToAdvice::factory()->create();
    $formDefinition = $creator->formDefinition;
    $formDefinition->update(['group_id' => $this->group->id]);

    $page = visit(route('form-definitions.edit', $formDefinition));

    $page->assertSee('Address')
        ->assertDontSee('Keine Adresse angegeben')
        ->assertSee('Straße')
        ->assertSee('PLZ')
        ->assertSee('Ort')
        ->assertNoJavaScriptErrors();
});

test('the public form renders a usable address field', function (): void {
    $creator = FormDefinitionToAdvice::factory()->create();
    $formDefinition = $creator->formDefinition;
    $formDefinition->update(['group_id' => $this->group->id]);

    $page = visit(route('form.show', $formDefinition));

    $page->assertSee('Straße')
        ->assertDontSee('Keine Adresse angegeben')
        ->assertEnabled('#street')
        ->assertNoJavaScriptErrors();
});

/**
 * A form with a checkbox "Maßnahmen" and a number field "Breite", which depends on "Igeltor" when the condition is set.
 *
 * @return array{0: FormDefinition, 1: FormField, 2: FormField}
 */
function formWithCharacteristicCheckbox(Group $group, bool $withCondition): array
{
    $formDefinition = FormDefinition::factory()->for($group)->create(['is_active' => true]);
    $characteristics = $formDefinition->fields()->create(['type' => FieldType::CHECKBOX, 'label' => 'Maßnahmen', 'required' => false, 'sort_order' => 0]);
    $characteristics->options()->create(['label' => 'Igeltor', 'value' => 'gate', 'sort_order' => 0]);
    $characteristics->options()->create(['label' => 'Totholz', 'value' => 'deadwood', 'sort_order' => 1]);
    $width = $formDefinition->fields()->create(['type' => FieldType::NUMBER, 'label' => 'Breite', 'required' => false, 'sort_order' => 1]);

    if ($withCondition) {
        $width->visibleIfField()->associate($characteristics);
        $width->update(['visible_if_option_value' => 'gate']);
    }

    return [$formDefinition, $characteristics, $width];
}

test('the public form shows the fields of an option only while it is checked', function (): void {
    [$formDefinition] = formWithCharacteristicCheckbox($this->group, withCondition: true);

    $page = visit(route('form.show', $formDefinition));

    $page->assertSee('Totholz')
        ->assertDontSee('Breite')
        ->click('Igeltor')
        ->assertVisible('[data-test="dependent-fields"]')
        ->assertSee('Breite')
        ->click('Igeltor')
        ->assertDontSee('Breite')
        ->assertNoJavaScriptErrors();
});

test('a field can be shown only for an option in the form builder', function (): void {
    [$formDefinition, $characteristics, $width] = formWithCharacteristicCheckbox($this->group, withCondition: false);

    $page = visit(route('form-definitions.edit', $formDefinition));

    $page->click('.form-field:nth-child(2)')
        ->click('#field_visible_if_field')
        ->click('Nur wenn „Maßnahmen“ …')
        ->assertSee('… diese Option hat')
        ->assertSee('Nur wenn „Maßnahmen“: Igeltor')
        ->click('Speichern')
        ->assertNoJavaScriptErrors();

    expect($width->fresh()->only(['visible_if_field_id', 'visible_if_option_value']))
        ->toBe(['visible_if_field_id' => $characteristics->id, 'visible_if_option_value' => 'gate']);
});
