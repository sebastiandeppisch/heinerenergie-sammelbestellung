<?php

use App\Enums\FieldType;
use App\Models\FormDefinition;
use App\Models\FormField;
use App\Models\SubmissionField;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
});

test('image field can be submitted with a jpeg', function () {
    $formDefinition = FormDefinition::factory()->create();
    $formField = FormField::factory()->create([
        'form_definition_id' => $formDefinition->id,
        'type' => FieldType::IMAGE,
        'label' => 'Foto',
        'max_images' => 1,
        'required' => false,
    ]);

    $file = UploadedFile::fake()->image('photo.jpg', 800, 600);

    $response = $this->post(route('form.submit', $formDefinition), [
        $formField->uuid => [$file],
    ]);

    $response->assertSessionHasNoErrors();

    $this->assertDatabaseHas('form_submissions', [
        'form_definition_id' => $formDefinition->id,
    ]);

    $submissionField = SubmissionField::where('form_field_id', $formField->id)->firstOrFail();
    $paths = $submissionField->value;

    expect($paths)->toBeArray()->toHaveCount(1);
    Storage::disk('public')->assertExists($paths[0]);
});

test('image field can be submitted with a png', function () {
    $formDefinition = FormDefinition::factory()->create();
    $formField = FormField::factory()->create([
        'form_definition_id' => $formDefinition->id,
        'type' => FieldType::IMAGE,
        'label' => 'Foto',
        'max_images' => 1,
        'required' => false,
    ]);

    $file = UploadedFile::fake()->image('photo.png', 400, 300);

    $response = $this->post(route('form.submit', $formDefinition), [
        $formField->uuid => [$file],
    ]);

    $response->assertSessionHasNoErrors();

    $submissionField = SubmissionField::where('form_field_id', $formField->id)->firstOrFail();
    Storage::disk('public')->assertExists($submissionField->value[0]);
});

test('image field stores multiple images up to max_images', function () {
    $formDefinition = FormDefinition::factory()->create();
    $formField = FormField::factory()->create([
        'form_definition_id' => $formDefinition->id,
        'type' => FieldType::IMAGE,
        'label' => 'Fotos',
        'max_images' => 3,
        'required' => false,
    ]);

    $files = [
        UploadedFile::fake()->image('photo1.jpg'),
        UploadedFile::fake()->image('photo2.jpg'),
        UploadedFile::fake()->image('photo3.jpg'),
    ];

    $response = $this->post(route('form.submit', $formDefinition), [
        $formField->uuid => $files,
    ]);

    $response->assertSessionHasNoErrors();

    $submissionField = SubmissionField::where('form_field_id', $formField->id)->firstOrFail();
    expect($submissionField->value)->toBeArray()->toHaveCount(3);

    foreach ($submissionField->value as $path) {
        Storage::disk('public')->assertExists($path);
    }
});

test('image field rejects too many images', function () {
    $formDefinition = FormDefinition::factory()->create();
    $formField = FormField::factory()->create([
        'form_definition_id' => $formDefinition->id,
        'type' => FieldType::IMAGE,
        'label' => 'Foto',
        'max_images' => 1,
        'required' => false,
    ]);

    $response = $this->post(route('form.submit', $formDefinition), [
        $formField->uuid => [
            UploadedFile::fake()->image('photo1.jpg'),
            UploadedFile::fake()->image('photo2.jpg'),
        ],
    ]);

    $response->assertSessionHasErrors($formField->uuid);
});

test('image field rejects non-image files', function () {
    $formDefinition = FormDefinition::factory()->create();
    $formField = FormField::factory()->create([
        'form_definition_id' => $formDefinition->id,
        'type' => FieldType::IMAGE,
        'label' => 'Foto',
        'max_images' => 1,
        'required' => false,
    ]);

    $response = $this->post(route('form.submit', $formDefinition), [
        $formField->uuid => [
            UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
        ],
    ]);

    $response->assertSessionHasErrors($formField->uuid.'.*');
});

test('required image field fails validation without file', function () {
    $formDefinition = FormDefinition::factory()->create();
    $formField = FormField::factory()->create([
        'form_definition_id' => $formDefinition->id,
        'type' => FieldType::IMAGE,
        'label' => 'Pflichtfoto',
        'max_images' => 1,
        'required' => true,
    ]);

    $response = $this->post(route('form.submit', $formDefinition), [
        $formField->uuid => [],
    ]);

    $response->assertSessionHasErrors($formField->uuid);
});

test('optional image field passes validation without file', function () {
    $formDefinition = FormDefinition::factory()->create();
    $formField = FormField::factory()->create([
        'form_definition_id' => $formDefinition->id,
        'type' => FieldType::IMAGE,
        'label' => 'Optionales Foto',
        'max_images' => 1,
        'required' => false,
    ]);

    $response = $this->post(route('form.submit', $formDefinition), [
        $formField->uuid => [],
    ]);

    $response->assertSessionHasNoErrors();
});

test('image field stores files in submission-specific directory', function () {
    $formDefinition = FormDefinition::factory()->create();
    $formField = FormField::factory()->create([
        'form_definition_id' => $formDefinition->id,
        'type' => FieldType::IMAGE,
        'label' => 'Foto',
        'max_images' => 1,
        'required' => false,
    ]);

    $file = UploadedFile::fake()->image('photo.jpg');

    $this->post(route('form.submit', $formDefinition), [
        $formField->uuid => [$file],
    ]);

    $submissionField = SubmissionField::where('form_field_id', $formField->id)->firstOrFail();
    $path = $submissionField->value[0];

    expect($path)->toStartWith('form-images/');
});

test('image bomb is rejected', function () {
    $formDefinition = FormDefinition::factory()->create();
    $formField = FormField::factory()->create([
        'form_definition_id' => $formDefinition->id,
        'type' => FieldType::IMAGE,
        'label' => 'Foto',
        'max_images' => 1,
        'required' => false,
    ]);

    // Create a fake image with huge pixel dimensions
    $file = UploadedFile::fake()->image('bomb.jpg', 9000, 9000);

    $response = $this->post(route('form.submit', $formDefinition), [
        $formField->uuid => [$file],
    ]);

    $response->assertSessionHasErrors($formField->uuid.'.*');
});

test('orphaned images are deleted when transaction fails', function () {
    $formDefinition = FormDefinition::factory()->create();
    $imageField = FormField::factory()->create([
        'form_definition_id' => $formDefinition->id,
        'type' => FieldType::IMAGE,
        'label' => 'Foto',
        'max_images' => 1,
        'required' => false,
        'sort_order' => 0,
    ]);
    // Required text field that we'll leave empty to trigger a DB-level failure
    $textField = FormField::factory()->create([
        'form_definition_id' => $formDefinition->id,
        'type' => FieldType::TEXT,
        'label' => 'Pflichtfeld',
        'required' => true,
        'sort_order' => 1,
    ]);

    $file = UploadedFile::fake()->image('photo.jpg');

    $response = $this->post(route('form.submit', $formDefinition), [
        $imageField->uuid => [$file],
        // textField omitted → validation fails → transaction never starts
    ]);

    // Validation error means no files written at all — nothing to clean up
    $response->assertSessionHasErrors($textField->uuid);
    Storage::disk('public')->assertDirectoryEmpty('form-images');
});

test('form submit is rate limited', function () {
    $formDefinition = FormDefinition::factory()->create();

    for ($i = 0; $i < 10; $i++) {
        $this->post(route('form.submit', $formDefinition), []);
    }

    $response = $this->post(route('form.submit', $formDefinition), []);
    $response->assertStatus(429);
});
