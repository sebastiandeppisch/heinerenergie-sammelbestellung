<?php

use App\Enums\FieldType;
use App\Models\FormDefinition;
use App\Models\FormField;
use App\Models\FormFieldOption;
use App\Models\Group;
use App\Models\SubmissionField;
use App\Models\User;
use App\Services\SessionService;
use App\ValueObjects\Coordinate;
use Inertia\Testing\AssertableInertia as Assert;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {

});


test('form with text field can be submitted', function () {
    $formDefinition = FormDefinition::factory()->create();
    $formField = FormField::factory()->create([
        'form_definition_id' => $formDefinition->id,
        'type' => FieldType::TEXT,
        'label' => 'Test Text Field',
    ]);

    $this->withoutExceptionHandling();

    $response = $this->post(route('form.submit', ['formDefinition' => $formDefinition->id]), [
        $formField->id => 'Sample text input',
    ]);

    $response->assertSessionHasNoErrors();

    $this->assertDatabaseHas('form_submissions', [
        'form_definition_id' => $formDefinition->id
    ]);
    $this->assertDatabaseHas('submission_fields', [
        'form_field_id' => $formField->id,
    ]);

    $field = SubmissionField::firstOrFail();
    $this->assertSame('Sample text input', $field->value);
});


test('form with number field can be submitted', function () {
    $formDefinition = FormDefinition::factory()->create();
    $formField = FormField::factory()->create([
        'form_definition_id' => $formDefinition->id,
        'type' => FieldType::NUMBER,
        'label' => 'Test Number Field',
    ]);

    $this->withoutExceptionHandling();

    $response = $this->post(route('form.submit', ['formDefinition' => $formDefinition->id]), [
        $formField->id => 123,
    ]);

    $response->assertSessionHasNoErrors();

    $this->assertDatabaseHas('form_submissions', [
        'form_definition_id' => $formDefinition->id
    ]);
    $this->assertDatabaseHas('submission_fields', [
        'form_field_id' => $formField->id,
    ]);

    $field = SubmissionField::firstOrFail();
    $this->assertSame(123, $field->value);
});

test('form with single select can be submitted', function () {
    $formDefinition = FormDefinition::factory()->create();
    $formField = FormField::factory()->create([
        'form_definition_id' => $formDefinition->id,
        'type' => FieldType::SELECT,
        'label' => 'Test Select Field',
    ]);

    $option1 = FormFieldOption::factory()->create([
        'form_field_id' => $formField->id,
        'label' => 'Option 1',
    ]);
    $option2 = FormFieldOption::factory()->create([
        'form_field_id' => $formField->id,
        'label' => 'Option 2',
    ]);

    $this->withoutExceptionHandling();

    $response = $this->post(route('form.submit', ['formDefinition' => $formDefinition->id]), [
        $formField->id => $option1->id,
    ]);

    $response->assertSessionHasNoErrors();

    $this->assertDatabaseHas('form_submissions', [
        'form_definition_id' => $formDefinition->id
    ]);
    $this->assertDatabaseHas('submission_fields', [
        'form_field_id' => $formField->id,
        'value' => json_encode($option1->id),
    ]);

    $field = SubmissionField::firstOrFail();
    $this->assertSame($option1->id, $field->value);
});
test('form with multiple select can be submitted', function () {
    $formDefinition = FormDefinition::factory()->create();
    $formField = FormField::factory()->create([
        'form_definition_id' => $formDefinition->id,
        'type' => FieldType::SELECT,
        'label' => 'Test Multi Select Field',
        'is_multiple' => true,
    ]);

    $option1 = FormFieldOption::factory()->create([
        'form_field_id' => $formField->id,
        'label' => 'Option 1',
    ]);
    $option2 = FormFieldOption::factory()->create([
        'form_field_id' => $formField->id,
        'label' => 'Option 2',
    ]);

    $this->withoutExceptionHandling();

    $response = $this->post(route('form.submit', ['formDefinition' => $formDefinition->id]), [
        $formField->id => [$option1->id, $option2->id],
    ]);

    $response->assertSessionHasNoErrors();

    $this->assertDatabaseHas('form_submissions', [
        'form_definition_id' => $formDefinition->id
    ]);
    $this->assertDatabaseHas('submission_fields', [
        'form_field_id' => $formField->id,
        'value' => json_encode([$option1->id, $option2->id]),
    ]);

    $field = SubmissionField::firstOrFail();
    $this->assertSame(json_encode([$option1->id, $option2->id]), $field->value);
})->skip('Multiselect not implemented yet');

test('Selectboxes with multiple values can be submitted', function () {
    $formDefinition = FormDefinition::factory()->create();
    $formField = FormField::factory()->create([
        'form_definition_id' => $formDefinition->id,
        'type' => FieldType::SELECT,
        'label' => 'Test Multi Select Field',
    ]);

    $option1 = FormFieldOption::factory()->create([
        'form_field_id' => $formField->id,
        'label' => 'Option 1',
    ]);
    $option2 = FormFieldOption::factory()->create([
        'form_field_id' => $formField->id,
        'label' => 'Option 2',
    ]);

    $this->withoutExceptionHandling();

    $response = $this->post(route('form.submit', ['formDefinition' => $formDefinition->id]), [
        $formField->id => [$option1->id, $option2->id],
    ]);

    $response->assertSessionHasNoErrors();

    $this->assertDatabaseHas('form_submissions', [
        'form_definition_id' => $formDefinition->id
    ]);
    $this->assertDatabaseHas('submission_fields', [
        'form_field_id' => $formField->id,
        'value' => json_encode([$option1->id, $option2->id]),
    ]);

    $field = SubmissionField::firstOrFail();
    $this->assertSame([$option1->id, $option2->id], $field->value);
});

test('form with radio button can be submitted', function(){
    $formDefinition = FormDefinition::factory()->create();
    $formField = FormField::factory()->create([
        'form_definition_id' => $formDefinition->id,
        'type' => FieldType::RADIO,
        'label' => 'Test Radio Field',
    ]);

    $option1 = FormFieldOption::factory()->create([
        'form_field_id' => $formField->id,
        'label' => 'Option 1',
    ]);
    $option2 = FormFieldOption::factory()->create([
        'form_field_id' => $formField->id,
        'label' => 'Option 2',
    ]);

    $this->withoutExceptionHandling();

    $response = $this->post(route('form.submit', ['formDefinition' => $formDefinition->id]), [
        $formField->id => $option1->id,
    ]);

    $response->assertSessionHasNoErrors();

    $this->assertDatabaseHas('form_submissions', [
        'form_definition_id' => $formDefinition->id
    ]);
    $this->assertDatabaseHas('submission_fields', [
        'form_field_id' => $formField->id,
        'value' => json_encode($option1->id),
    ]);

    $field = SubmissionField::firstOrFail();
    $this->assertSame($option1->id, $field->value);
});


test('form with radiobutton is validated', function () {
    $formDefinition = FormDefinition::factory()->create();
    $formField = FormField::factory()->create([
        'form_definition_id' => $formDefinition->id,
        'type' => FieldType::RADIO,
        'label' => 'Test Radio Field',
    ]);


    $response = $this->post(route('form.submit', ['formDefinition' => $formDefinition->id]), [
        $formField->id => 'random_value', // Invalid value
    ]);

    $response->assertSessionHasErrors($formField->id);
});


test('geoposition can be submitted', function(){
    $formDefinition = FormDefinition::factory()->create();
    $formField = FormField::factory()->create([
        'form_definition_id' => $formDefinition->id,
        'type' => FieldType::GEO_COORDINATE,
        'label' => 'Your example location'
    ]);

    $this->withoutExceptionHandling();

    $response = $this->post(route('form.submit', ['formDefinition' => $formField->formDefinition->id]), [
        $formField->id => [
            'lat' => 49,
            'lng' => 10
        ],
    ]);

    $response->assertSessionHasNoErrors();

    $field = SubmissionField::firstOrFail();
    $this->assertEquals(FieldType::GEO_COORDINATE, $field->field_type);

    $this->assertEquals(new Coordinate(49, 10), $field->asCoordinate());
});


it('validates geographic coordinates', function () {
    $formDefinition = FormDefinition::factory()->create();
    $formField = FormField::factory()->create([
        'form_definition_id' => $formDefinition->id,
        'type' => FieldType::GEO_COORDINATE,
        'label' => 'Your example location'
    ]);

    $response = $this->post(route('form.submit', ['formDefinition' => $formField->formDefinition->id]), [
        $formField->id => [
            'lat' => 120,
            'lng' => 190
        ],
    ]);

    $response->assertSessionHasErrors();
});


test('submitting a required checkbox options produces a validation error', function () {
    $formDefinition = FormDefinition::factory()->create();
    $formField = FormField::factory()->create([
        'form_definition_id' => $formDefinition->id,
        'type' => FieldType::CHECKBOX,
        'label' => 'Test Checkbox Field',
    ]);

    $option1 = FormFieldOption::factory()->create([
        'form_field_id' => $formField->id,
        'label' => 'Option 1',
        'is_required' => true,
    ]);
    $option2 = FormFieldOption::factory()->create([
        'form_field_id' => $formField->id,
        'label' => 'Option 2',
        'is_required' => true,
    ]);
    $option3 = FormFieldOption::factory()->create([
        'form_field_id' => $formField->id,
        'label' => 'Option 3',
        'is_required' => false,
    ]);


    $response = $this->post(route('form.submit', ['formDefinition' => $formDefinition->id]), [
        $formField->id => [],
    ]);

    $response->assertSessionHasErrors($formField->id);
    $response->assertSessionHasErrors([
        $formField->id => 'Es müssen die folgenden Optionen ausgewählt werden: Option 1, Option 2',
    ]);
});

test('submitting a checkbox with required options passes validation when all required options are selected', function () {
    $formDefinition = FormDefinition::factory()->create();
    $formField = FormField::factory()->create([
        'form_definition_id' => $formDefinition->id,
        'type' => FieldType::CHECKBOX,
        'label' => 'Test Checkbox Field',
    ]);

    $option1 = FormFieldOption::factory()->create([
        'form_field_id' => $formField->id,
        'label' => 'Option 1',
        'is_required' => true,
    ]);
    $option2 = FormFieldOption::factory()->create([
        'form_field_id' => $formField->id,
        'label' => 'Option 2',
        'is_required' => true,
    ]);
    $option3 = FormFieldOption::factory()->create([
        'form_field_id' => $formField->id,
        'label' => 'Option 3',
        'is_required' => false,
    ]);

    $response = $this->post(route('form.submit', ['formDefinition' => $formDefinition->id]), [
        $formField->id => [$option1->id, $option2->id],
    ]);

    $response->assertSessionHasNoErrors();
});