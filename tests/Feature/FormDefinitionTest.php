<?php

use App\Data\FormDefinitionData;
use App\Enums\FieldType;
use App\Models\FormDefinition;
use App\Models\FormField;
use App\Models\FormFieldOption;
use App\Models\Group;
use App\Models\User;
use App\Services\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->group = Group::factory()->create(['name' => 'Test Initiative']);
    $this->group->users()->attach($this->user, ['is_admin' => true]);
    app(SessionService::class)->actAsGroup($this->group);
    $this->actingAs($this->user);
});

test('index page can be rendered without data', function () {

    $this->withoutExceptionHandling();

    $response = $this->get(route('form-definitions.index'));

    $response->assertStatus(200);
    $response->assertInertia(fn (Assert $page) => $page
        ->component('FormBuilder/Index')
        ->has('formDefinitions', 0)
    );
});

test('formbuilder index page can be rendered with empty form definitions', function () {
    $formDefinitions = FormDefinition::factory(3)->create();

    $response = $this->get(route('form-definitions.index'));

    $response->assertStatus(200);
    $response->assertInertia(fn (Assert $page) => $page
        ->component('FormBuilder/Index')
        ->has('formDefinitions', 3)
    );
});

test('index page can be rendered with form definitions', function () {
    $formDefinitions = FormDefinition::factory(3)->withFields(10)->create();

    $response = $this->get(route('form-definitions.index'));

    $response->assertStatus(200);
    $response->assertInertia(fn (Assert $page) => $page
        ->component('FormBuilder/Index')
        ->has('formDefinitions', 3)
        ->where('formDefinitions.0.id', $formDefinitions[0]->uuid)
        ->where('formDefinitions.1.id', $formDefinitions[1]->uuid)
        ->where('formDefinitions.2.id', $formDefinitions[2]->uuid)
    );
});

test('formbuilder create page can be rendered', function () {
    $response = $this->get(route('form-definitions.create'));

    $response->assertStatus(200);
    $response->assertInertia(fn (Assert $page) => $page
        ->component('FormBuilder/Edit')
        ->where('formDefinition', null)
        ->has('fieldTypes')
    );
});

test('formbuilder edit page can be rendered', function () {
    $formDefinition = FormDefinition::factory()
        ->hasFields(3)
        ->create();

    $formFields = $formDefinition->fields;

    foreach ($formFields as $field) {
        if (in_array($field->type, [FieldType::SELECT->value, FieldType::RADIO->value, FieldType::CHECKBOX->value])) {
            FormFieldOption::factory()->count(2)->create([
                'form_field_id' => $field->id,
            ]);
        }
    }

    $response = $this->get(route('form-definitions.edit', $formDefinition));

    $response->assertStatus(200);
    $response->assertInertia(fn (Assert $page) => $page
        ->component('FormBuilder/Edit')
        ->has('formDefinition')
        ->has('formDefinition.fields')
        ->has('fieldTypes')
    );
});

test('form definition can be created', function () {

    $this->withoutExceptionHandling();

    $formData = [
        'name' => 'Test Form',
        'description' => 'This is a test form',
        'is_active' => true,
        'id' => 'temp',
        'group_id' => $this->group->uuid,
        'fields' => [
            [
                'type' => FieldType::TEXT->value,
                'name' => 'text_field',
                'label' => 'Text Field',
                'placeholder' => 'Enter text',
                'required' => true,
                'id' => 'temp',
                'options' => [],
            ],
            [
                'type' => FieldType::SELECT->value,
                'name' => 'select_field',
                'label' => 'Select Field',
                'required' => false,
                'id' => 'temp',
                'options' => [
                    [
                        'label' => 'Option 1',
                        'value' => 'option1',
                        'is_default' => true,
                        'sort_order' => 1,
                        'id' => 'temp',
                        'is_required' => false,
                    ],
                    [
                        'label' => 'Option 2',
                        'value' => 'option2',
                        'is_default' => false,
                        'sort_order' => 2,
                        'id' => 'temp',
                        'is_required' => false,
                    ],
                ],
            ],
        ],
    ];

    $response = $this->post(route('form-definitions.store'), $formData);
    $response->assertSessionHasNoErrors();
    $response->assertStatus(302);

    $formDefinition = FormDefinition::firstOrFail();

    $response->assertRedirect(route('form-definitions.edit', $formDefinition));
    $response->assertSessionHas('success');

    // Überprüfe, ob die Formular-Definition in der Datenbank existiert
    $this->assertDatabaseHas('form_definitions', [
        'name' => 'Test Form',
        'description' => 'This is a test form',
        'is_active' => true,
    ]);

    // Überprüfe, ob die Formular-Felder erstellt wurden
    $this->assertDatabaseHas('form_fields', [
        'form_definition_id' => $formDefinition->id,
        'type' => FieldType::TEXT->value,
        'label' => 'Text Field',
        'placeholder' => 'Enter text',
        'required' => true,
    ]);

    $this->assertDatabaseHas('form_fields', [
        'form_definition_id' => $formDefinition->id,
        'type' => FieldType::SELECT->value,
        'label' => 'Select Field',
        'required' => false,
    ]);

    // Überprüfe, ob die Optionen für das Select-Feld erstellt wurden
    $selectField = FormField::where('label', 'Select Field')->first();

    $this->assertDatabaseHas('form_field_options', [
        'form_field_id' => $selectField->id,
        'label' => 'Option 1',
        'value' => 'option1',
        'is_default' => true,
    ]);

    $this->assertDatabaseHas('form_field_options', [
        'form_field_id' => $selectField->id,
        'label' => 'Option 2',
        'value' => 'option2',
        'is_default' => false,
    ]);
});

test('form definition can be updated', function () {
    // Erstelle ein Formular mit Feldern
    $formDefinition = FormDefinition::factory()->create([
        'name' => 'Original Form',
        'description' => 'Original description',
    ]);

    $field = FormField::factory()->create([
        'form_definition_id' => $formDefinition->id,
        'type' => FieldType::TEXT->value,
        'label' => 'Original Field',
    ]);

    // Aktualisierte Formulardaten
    $updatedData = [
        'name' => 'Updated Form',
        'description' => 'Updated description',
        'is_active' => true,
        'group_id' => $this->group->uuid,
        'id' => $formDefinition->uuid,
        'fields' => [
            [
                'type' => FieldType::TEXTAREA->value,
                'name' => 'new_field',
                'label' => 'New Field',
                'placeholder' => 'Enter text here',
                'required' => true,
                'id' => $field->uuid,
                'options' => [],
            ],
        ],
    ];

    $response = $this->put(route('form-definitions.update', $formDefinition), $updatedData);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    // Überprüfe, ob die Formular-Definition aktualisiert wurde
    $this->assertDatabaseHas('form_definitions', [
        'id' => $formDefinition->id,
        'name' => 'Updated Form',
        'description' => 'Updated description',
    ]);

    // Überprüfe, ob das Feld aktualisiert wurde
    $this->assertDatabaseHas('form_fields', [
        'form_definition_id' => $formDefinition->id,
        'type' => FieldType::TEXTAREA->value,
        'label' => 'New Field',
        'placeholder' => 'Enter text here',
        'required' => true,
    ]);
});

test('form definition can be deleted', function () {
    // Erstelle ein Formular mit Feldern und Optionen
    $formDefinition = FormDefinition::factory()->withFields(10)->create();

    $response = $this->delete(route('form-definitions.destroy', $formDefinition));

    $response->assertRedirect(route('form-definitions.index'));
    $response->assertSessionHas('success');

    // Überprüfe, ob die Formular-Definition gelöscht wurde
    $this->assertDatabaseMissing('form_definitions', [
        'id' => $formDefinition->id,
    ]);

});

todo('Should only admins create forms or can regular users view forms?');

test('form fields can be saved with required field', function () {

    $this->withoutExceptionHandling();

    $response = $this->post(route('form-definitions.store'), [
        'name' => 'Test Form',
        'id' => (string) Str::uuid7(),
        'is_active' => true,
        'group_id' => $this->group->uuid,
        'fields' => [
            [
                'type' => FieldType::TEXTAREA->value,
                'label' => 'Text Field',
                'required' => true,
                'form_definition_id' => 'some id',
                'id' => (string) Str::uuid7(),
                'options' => [],
            ],
        ]]);

    $response->assertSessionHasNoErrors();

    $this->assertTrue(FormField::firstOrFail()->required);

});

test('form fields can be updated to be required', function () {
    $this->withoutExceptionHandling();
    FormDefinition::factory()->withFields(1)->create();

    FormField::firstOrFail()->update([
        'required' => false,
        'type' => FieldType::TEXTAREA->value,
    ]);
    $this->assertFalse(FormField::first()->required);

    $data = FormDefinitionData::fromModel(FormDefinition::firstOrFail());

    $data->fields[0]->required = true;
    $response = $this->put(route('form-definitions.update', [FormDefinition::firstOrFail()]), $data->toArray());

    $response->assertSessionHasNoErrors();

    $this->assertTrue(FormField::first()->required);
});

test('form fields are updated in-place', function () {
    $this->withoutExceptionHandling();

    $id = FormDefinition::factory()->withFields()->create()->id;

    $ids = FormDefinition::firstOrFail()->fields()->pluck('id');

    $data = FormDefinitionData::fromModel(FormDefinition::firstOrFail());

    $this->put(route('form-definitions.update', [FormDefinition::find($id)]), $data->toArray())->assertSessionHasNoErrors();

    $this->assertEquals(1, FormDefinition::count());
    $this->assertEquals(count($ids), FormField::count());
    $this->assertEquals($id, FormDefinition::firstOrFail()->id);
    $this->assertEquals($ids, FormDefinition::firstOrFail()->fields()->pluck('id'));
});

test('form fields can be deleted', function () {
    $this->withoutExceptionHandling();

    $id = FormDefinition::factory()->withFields(3)->create()->id;

    $ids = FormDefinition::firstOrFail()->fields()->pluck('id');
    $ids->forget(1);
    $ids = $ids->values();

    $data = FormDefinitionData::fromModel(FormDefinition::firstOrFail());

    unset($data->fields[1]);

    $this->put(route('form-definitions.update', [FormDefinition::find($id)]), $data->toArray())->assertSessionHasNoErrors();

    $this->assertEquals(1, FormDefinition::count());
    $this->assertEquals(2, FormField::count());
    $this->assertEquals($id, FormDefinition::firstOrFail()->id);
    $this->assertEquals($ids, FormDefinition::firstOrFail()->fields()->pluck('id'));
});

test('form field options can be deleted', function () {
    $this->withoutExceptionHandling();

    $formDefinition = FormDefinition::factory()->withFields(1)->create();
    $field = $formDefinition->fields->first();
    $field->options()->delete();
    $field->options()->createMany([
        ['label' => 'Option 1', 'value' => 'option1', 'is_default' => false, 'sort_order' => 1],
        ['label' => 'Option 2', 'value' => 'option2', 'is_default' => false, 'sort_order' => 2],
    ]);

    $data = FormDefinitionData::fromModel($formDefinition);
    unset($data->fields[0]->options[1]);
    $optionsId = $data->fields[0]->options[0]->id;

    $this->put(route('form-definitions.update', [$formDefinition]), $data->toArray())->assertSessionHasNoErrors();

    $this->assertEquals(1, FormField::count());
    $this->assertEquals(1, FormField::first()->options()->count());

    $this->assertEquals($optionsId, FormField::first()->options->first()->uuid);
});
