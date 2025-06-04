<?php

use App\Enums\FieldType;
use App\Models\FormDefinition;
use App\Models\FormField;
use App\Models\FormFieldOption;
use App\Models\Group;
use App\Models\User;
use App\Services\SessionService;
use Inertia\Testing\AssertableInertia as Assert;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->group = Group::factory()->create(['name' => 'Test Initiative']);
    $this->group->users()->attach($this->user, ['is_admin' => true]);
    app(SessionService::class)->actAsGroup($this->group);
    $this->actingAs($this->user);
});

test('index page can be rendered without data', function () {
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
        ->where('formDefinitions.0.id', $formDefinitions[0]->id)
        ->where('formDefinitions.1.id', $formDefinitions[1]->id)
        ->where('formDefinitions.2.id', $formDefinitions[2]->id)
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
                'form_field_id' => $field->id
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
    $formData = [
        'name' => 'Test Form',
        'description' => 'This is a test form',
        'is_active' => true,
        'fields' => [
            [
                'type' => FieldType::TEXT->value,
                'name' => 'text_field',
                'label' => 'Text Field',
                'placeholder' => 'Enter text',
                'required' => true
            ],
            [
                'type' => FieldType::SELECT->value,
                'name' => 'select_field',
                'label' => 'Select Field',
                'required' => false,
                'options' => [
                    [
                        'label' => 'Option 1',
                        'value' => 'option1',
                        'is_default' => true
                    ],
                    [
                        'label' => 'Option 2',
                        'value' => 'option2',
                        'is_default' => false
                    ]
                ]
            ]
        ]
    ];

    $response = $this->post(route('form-definitions.store'), $formData);


    $formDefinition = FormDefinition::firstOrFail();

    $response->assertRedirect(route('form-definitions.edit', $formDefinition));
    $response->assertSessionHas('success');

    // Überprüfe, ob die Formular-Definition in der Datenbank existiert
    $this->assertDatabaseHas('form_definitions', [
        'name' => 'Test Form',
        'description' => 'This is a test form',
        'is_active' => true
    ]);

    // Überprüfe, ob die Formular-Felder erstellt wurden
    $this->assertDatabaseHas('form_fields', [
        'form_definition_id' => $formDefinition->id,
        'type' => FieldType::TEXT->value,
        'name' => 'text_field',
        'label' => 'Text Field',
        'placeholder' => 'Enter text',
        'required' => true
    ]);

    $this->assertDatabaseHas('form_fields', [
        'form_definition_id' => $formDefinition->id,
        'type' => FieldType::SELECT->value,
        'name' => 'select_field',
        'label' => 'Select Field',
        'required' => false
    ]);

    // Überprüfe, ob die Optionen für das Select-Feld erstellt wurden
    $selectField = FormField::where('name', 'select_field')->first();

    $this->assertDatabaseHas('form_field_options', [
        'form_field_id' => $selectField->id,
        'label' => 'Option 1',
        'value' => 'option1',
        'is_default' => true
    ]);

    $this->assertDatabaseHas('form_field_options', [
        'form_field_id' => $selectField->id,
        'label' => 'Option 2',
        'value' => 'option2',
        'is_default' => false
    ]);
});

test('form definition can be updated', function () {
    // Erstelle ein Formular mit Feldern
    $formDefinition = FormDefinition::factory()->create([
        'name' => 'Original Form',
        'description' => 'Original description'
    ]);

    $field = FormField::factory()->create([
        'form_definition_id' => $formDefinition->id,
        'type' => FieldType::TEXT->value,
        'name' => 'original_field',
        'label' => 'Original Field'
    ]);

    // Aktualisierte Formulardaten
    $updatedData = [
        'name' => 'Updated Form',
        'description' => 'Updated description',
        'is_active' => true,
        'fields' => [
            [
                'type' => FieldType::TEXTAREA->value,
                'name' => 'new_field',
                'label' => 'New Field',
                'placeholder' => 'Enter text here',
                'required' => true
            ]
        ]
    ];

    $response = $this->put(route('form-definitions.update', $formDefinition), $updatedData);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    // Überprüfe, ob die Formular-Definition aktualisiert wurde
    $this->assertDatabaseHas('form_definitions', [
        'id' => $formDefinition->id,
        'name' => 'Updated Form',
        'description' => 'Updated description'
    ]);

    // Überprüfe, ob das alte Feld gelöscht wurde
    $this->assertDatabaseMissing('form_fields', [
        'id' => $field->id,
        'name' => 'original_field'
    ]);

    // Überprüfe, ob das neue Feld erstellt wurde
    $this->assertDatabaseHas('form_fields', [
        'form_definition_id' => $formDefinition->id,
        'type' => FieldType::TEXTAREA->value,
        'name' => 'new_field',
        'label' => 'New Field',
        'placeholder' => 'Enter text here',
        'required' => true
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
        'id' => $formDefinition->id
    ]);

});

todo('Should only admins create forms or can regular users view forms?');
