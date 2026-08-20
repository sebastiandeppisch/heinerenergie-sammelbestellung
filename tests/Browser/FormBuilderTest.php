<?php

use App\Models\FormDefinitionToAdvice;
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
