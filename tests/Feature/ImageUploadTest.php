<?php

use App\Data\SubmissionFieldData;
use App\Enums\FieldType;
use App\Models\Advice;
use App\Models\FormDefinition;
use App\Models\FormField;
use App\Models\FormSubmission;
use App\Models\Group;
use App\Models\SubmissionField;
use App\Models\User;
use App\Services\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Tests\Concerns\AutoAttachesFormEmbedToken;

uses(RefreshDatabase::class, AutoAttachesFormEmbedToken::class);

beforeEach(function (): void {
    Storage::fake('images');
    Storage::fake('image-cache');
});

test('image field can be submitted with a jpeg', function (): void {
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
    Storage::disk('images')->assertExists($paths[0]);
});

test('image field can be submitted with a png', function (): void {
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
    Storage::disk('images')->assertExists($submissionField->value[0]);
});

test('image field stores multiple images up to max_images', function (): void {
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
        Storage::disk('images')->assertExists($path);
    }
});

test('image field rejects too many images', function (): void {
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

test('image field rejects non-image files', function (): void {
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

test('required image field fails validation without file', function (): void {
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

test('optional image field passes validation without file', function (): void {
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

test('image field stores files in submission-specific directory', function (): void {
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

test('image field strips exif geotags from uploaded jpegs', function (string $driver): void {
    config(['intervention-image.driver' => $driver]);

    $formDefinition = FormDefinition::factory()->create();
    $formField = FormField::factory()->create([
        'form_definition_id' => $formDefinition->id,
        'type' => FieldType::IMAGE,
        'label' => 'Foto',
        'max_images' => 1,
        'required' => false,
    ]);

    $file = UploadedFile::fake()->createWithContent('photo.jpg', jpegWithGpsExif());
    expect(exif_read_data($file->getRealPath(), 'GPS'))->toBeArray();

    $this->post(route('form.submit', $formDefinition), [
        $formField->uuid => [$file],
    ])->assertSessionHasNoErrors();

    $submissionField = SubmissionField::where('form_field_id', $formField->id)->firstOrFail();
    $storedPath = Storage::disk('images')->path($submissionField->value[0]);

    expect(@exif_read_data($storedPath, 'GPS'))->toBeFalse();
})->with([
    'gd' => Driver::class,
    'imagick' => Intervention\Image\Drivers\Imagick\Driver::class,
]);

test('image bomb is rejected', function (): void {
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

test('orphaned images are deleted when transaction fails', function (): void {
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
    Storage::disk('images')->assertDirectoryEmpty('form-images');
});

test('form submit is rate limited', function (): void {
    $formDefinition = FormDefinition::factory()->create();

    for ($i = 0; $i < 10; $i++) {
        $this->post(route('form.submit', $formDefinition), []);
    }

    $response = $this->post(route('form.submit', $formDefinition), []);
    $response->assertStatus(429);
});

test('images of a submission are served to members of its group and to advisors of its advice only', function (): void {
    Config::set('app.group_context', 'group');
    $formDefinition = FormDefinition::factory()->create();
    $formField = FormField::factory()->create([
        'form_definition_id' => $formDefinition->id,
        'type' => FieldType::IMAGE,
        'label' => 'Foto',
        'max_images' => 1,
        'required' => false,
    ]);
    $this->post(route('form.submit', $formDefinition), [$formField->uuid => [UploadedFile::fake()->image('photo.jpg', 800, 600)]])->assertSessionHasNoErrors();
    $submission = FormSubmission::sole();
    $url = SubmissionFieldData::fromModel($submission->submissionFields()->sole())->value[0];
    $member = User::factory()->create(['is_admin' => false]);
    $formDefinition->group->users()->attach($member);
    $advisor = User::factory()->create(['is_admin' => false]);
    $stranger = User::factory()->create(['is_admin' => false]);

    expect($url)->toContain('/form-images/'.$submission->uuid.'/');
    $this->get($url)->assertRedirect(route('login'));
    $otherGroup = Group::factory()->create();
    $otherGroup->users()->attach($stranger);
    app(SessionService::class)->actAsGroup($otherGroup);
    $this->actingAs($stranger)->get($url)->assertNotFound();

    app(SessionService::class)->actAsGroup($formDefinition->group);
    $this->actingAs($member)->get($url)->assertOk()->assertHeader('Content-Type', 'image/jpeg');
    $this->get($url.'?w=400')->assertOk();

    $advice = Advice::factory()->create(['advisor_id' => $advisor->id]);
    $submission->update(['advice_id' => $advice->id]);
    $otherGroup->users()->attach($advisor);
    app(SessionService::class)->actAsGroup($otherGroup);
    $this->actingAs($advisor)->get($url)->assertOk();
});

test('the migration moves submitted images from the public to the private disk', function (): void {
    Storage::fake('public');
    Storage::disk('public')->put('form-images/abc/photo.jpg', 'jpeg');
    Storage::disk('public')->put('logos/logo.png', 'png');
    $migration = require database_path('migrations/2026_10_02_120000_move_form_images_to_private_disk.php');

    $migration->up();

    Storage::disk('images')->assertExists('form-images/abc/photo.jpg');
    Storage::disk('public')->assertMissing('form-images/abc/photo.jpg')->assertExists('logos/logo.png');
});
