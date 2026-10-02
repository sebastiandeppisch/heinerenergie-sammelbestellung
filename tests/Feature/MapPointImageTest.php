<?php

use App\Data\MapPointSpreadsheetFieldData;
use App\Enums\FieldType;
use App\Exports\MapPointsExport;
use App\Models\FormDefinitionToMapPoint;
use App\Models\FormField;
use App\Models\Group;
use App\Models\MapEmbed;
use App\Models\MapPoint;
use App\Models\MapPointCategory;
use App\Models\User;
use App\Services\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Tests\Concerns\AutoAttachesFormEmbedToken;

uses(RefreshDatabase::class, AutoAttachesFormEmbedToken::class);

beforeEach(function (): void {
    Config::set('app.group_context', 'group');
    Storage::fake('images');
    Storage::fake('image-cache');

    $this->group = Group::factory()->create();
    $this->category = MapPointCategory::factory()->for($this->group)->create();
    $this->photo = $this->category->findOrCreateFormDefinition()->fields()->create([
        'type' => FieldType::IMAGE,
        'label' => 'Foto vom Igeltor',
        'required' => false,
        'sort_order' => 0,
        'max_images' => 3,
    ]);
});

function imageTestAdmin(Group $group): User
{
    $admin = User::factory()->create(['is_admin' => false]);
    $group->users()->attach($admin, ['is_admin' => true]);
    app(SessionService::class)->actAsGroup($group, true);

    return $admin;
}

/**
 * @param  array<int, string|UploadedFile>  $images
 * @return array<string, mixed>
 */
function imageTestPayload(MapPoint|Group $target, MapPointCategory $category, FormField $field, array|string $images): array
{
    $group = $target instanceof MapPoint ? $target->group : $target;

    return [
        'title' => 'Igeltor',
        'coordinate' => ['lat' => 49.8728, 'lng' => 8.6512],
        'published' => true,
        'group_id' => $group->uuid,
        'category_id' => $category->uuid,
        'field_values' => [$field->uuid => $images],
    ];
}

/**
 * @param  array<int, string|UploadedFile>  $images
 */
function saveImages(User $admin, MapPoint $mapPoint, MapPointCategory $category, FormField $field, array|string $images): void
{
    test()->actingAs($admin)
        ->put(route('mappoints.update', $mapPoint), imageTestPayload($mapPoint, $category, $field, $images))
        ->assertSessionHasNoErrors();
}

/**
 * @return array<int, string>
 */
function storedImagePaths(MapPoint $mapPoint, FormField $field): array
{
    return (array) $mapPoint->fields()->where('form_field_id', $field->id)->first()?->value;
}

test('uploaded images are stored privately, scaled down and without geotags', function (): void {
    $admin = imageTestAdmin($this->group);

    $this->actingAs($admin)->post(route('mappoints.store'), imageTestPayload($this->group, $this->category, $this->photo, [
        UploadedFile::fake()->image('big.jpg', 4000, 3000),
        UploadedFile::fake()->createWithContent('gps.jpg', jpegWithGpsExif()),
    ]))->assertSessionHasNoErrors();

    $mapPoint = MapPoint::sole();
    [$big, $gps] = storedImagePaths($mapPoint, $this->photo);

    expect($big)->toStartWith('map-point-images/'.$mapPoint->uuid.'/');
    Storage::disk('images')->assertExists([$big, $gps]);
    [$width, $height] = getimagesize(Storage::disk('images')->path($big));
    expect([$width, $height])->toBe([1920, 1440])
        ->and(@exif_read_data(Storage::disk('images')->path($gps), 'GPS'))->toBeFalse();
});

test('images are validated', function (Closure $images, string $errorKey): void {
    $mapPoint = MapPoint::factory()->for($this->group)->create(['category_id' => $this->category->id]);
    $other = MapPoint::factory()->for($this->group)->create(['category_id' => $this->category->id]);
    $admin = imageTestAdmin($this->group);
    saveImages($admin, $other, $this->category, $this->photo, [UploadedFile::fake()->image('other.jpg')]);
    $otherName = basename(storedImagePaths($other, $this->photo)[0]);

    $this->put(route('mappoints.update', $mapPoint), imageTestPayload($mapPoint, $this->category, $this->photo, $images($otherName)))
        ->assertSessionHasErrors("field_values.{$this->photo->uuid}{$errorKey}");
})->with([
    'too many images' => [fn (): array => array_map(fn (int $i) => UploadedFile::fake()->image("$i.jpg"), range(1, 4)), ''],
    'not an image' => [fn (): array => [UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf')], '.0'],
    'image of another point' => [fn (string $otherName): array => [$otherName], '.0'],
    'path instead of a name' => [fn (string $otherName): array => ['../'.$otherName], '.0'],
]);

test('removing and replacing images deletes their files and variants, sorting keeps them', function (): void {
    $mapPoint = MapPoint::factory()->for($this->group)->create(['category_id' => $this->category->id, 'published' => true]);
    $admin = imageTestAdmin($this->group);
    saveImages($admin, $mapPoint, $this->category, $this->photo, [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')]);
    [$first, $second] = storedImagePaths($mapPoint, $this->photo);
    $this->get(route('map-point-images.show', [$mapPoint, basename($first), 'w' => 400]))->assertOk();
    Storage::disk('image-cache')->assertExists('400/'.$first);

    saveImages($admin, $mapPoint, $this->category, $this->photo, [basename($second), basename($first)]);
    expect(storedImagePaths($mapPoint, $this->photo))->toBe([$second, $first]);

    saveImages($admin, $mapPoint, $this->category, $this->photo, [basename($second), UploadedFile::fake()->image('c.jpg')]);
    [, $third] = storedImagePaths($mapPoint, $this->photo);
    Storage::disk('images')->assertMissing($first)->assertExists([$second, $third]);
    Storage::disk('image-cache')->assertMissing('400/'.$first);

    saveImages($admin, $mapPoint, $this->category, $this->photo, '');
    expect($mapPoint->fields()->count())->toBe(0);
    Storage::disk('images')->assertMissing([$second, $third]);
});

test('former values keep their images until the point is deleted', function (): void {
    $mapPoint = MapPoint::factory()->for($this->group)->create(['category_id' => $this->category->id]);
    $admin = imageTestAdmin($this->group);
    saveImages($admin, $mapPoint, $this->category, $this->photo, [UploadedFile::fake()->image('a.jpg')]);
    [$path] = storedImagePaths($mapPoint, $this->photo);
    $this->get(route('map-point-images.show', [$mapPoint, basename($path), 'w' => 800]))->assertOk();

    $otherCategory = MapPointCategory::factory()->for($this->group)->create();
    $this->put(route('mappoints.update', $mapPoint), imageTestPayload($mapPoint, $otherCategory, $this->photo, []))->assertSessionHasNoErrors();
    Storage::disk('images')->assertExists($path);

    $this->delete(route('mappoints.destroy', $mapPoint))->assertRedirect();

    Storage::disk('images')->assertMissing($path);
    Storage::disk('image-cache')->assertMissing('800/'.$path);
    expect(Storage::disk('images')->allFiles())->toBe([]);
});

test('images of public fields of published points are served to anyone, others only to admins', function (): void {
    $mapPoint = MapPoint::factory()->for($this->group)->create(['category_id' => $this->category->id, 'published' => true]);
    $admin = imageTestAdmin($this->group);
    saveImages($admin, $mapPoint, $this->category, $this->photo, [UploadedFile::fake()->image('a.jpg', 1200, 900)]);
    $url = route('map-point-images.show', [$mapPoint, basename(storedImagePaths($mapPoint, $this->photo)[0])]);

    $this->get($url)->assertOk()->assertHeader('Content-Type', 'image/jpeg');
    Auth::logout();
    $this->get($url)->assertNotFound();

    $this->category->syncPublicFields([$this->photo->id]);
    $response = $this->get($url.'?w=400')->assertOk();
    expect(getimagesizefromstring($response->streamedContent())[0])->toBe(400)
        ->and($response->headers->get('Cache-Control'))->toContain('private');

    $this->get($url.'?w=123')->assertNotFound();

    $mapPoint->update(['published' => false]);
    $this->get($url)->assertNotFound();
});

test('making a field private hides its images at once, including cached variants', function (): void {
    $this->category->syncPublicFields([$this->photo->id]);
    $mapPoint = MapPoint::factory()->for($this->group)->create(['category_id' => $this->category->id, 'published' => true]);
    saveImages(imageTestAdmin($this->group), $mapPoint, $this->category, $this->photo, [UploadedFile::fake()->image('a.jpg')]);
    $url = route('map-point-images.show', [$mapPoint, basename(storedImagePaths($mapPoint, $this->photo)[0])]);
    Auth::logout();
    $this->get($url)->assertOk();
    $this->get($url.'?w=400')->assertOk();

    $this->category->syncPublicFields([]);

    $this->get($url)->assertNotFound();
    $this->get($url.'?w=400')->assertNotFound();
});

test('the public map sends only urls of the image route', function (): void {
    $this->category->syncPublicFields([$this->photo->id]);
    $mapPoint = MapPoint::factory()->for($this->group)->create(['category_id' => $this->category->id, 'published' => true]);
    saveImages(imageTestAdmin($this->group), $mapPoint, $this->category, $this->photo, [UploadedFile::fake()->image('a.jpg')]);
    $path = storedImagePaths($mapPoint, $this->photo)[0];
    $mapEmbed = MapEmbed::factory()->for($this->group)->create();
    $mapEmbed->mapPointCategories()->sync([$this->category->id]);

    $this->get(route('map.public', $mapEmbed))
        ->assertOk()
        ->assertDontSee($path, false)
        ->assertInertia(fn ($page) => $page
            ->where("pointsByCategory.{$this->category->uuid}.0.fields.0.value", null)
            ->where("pointsByCategory.{$this->category->uuid}.0.fields.0.display_value", '1 Bild')
            ->where("pointsByCategory.{$this->category->uuid}.0.fields.0.images.0.url", route('map-point-images.show', [$mapPoint, basename($path)]))
        );
});

test('images of a submission are copied to the created point', function (): void {
    $config = FormDefinitionToMapPoint::factory()->create();
    $form = $config->formDefinition;
    $category = MapPointCategory::factory()->for($form->group)->create();
    $target = $category->findOrCreateFormDefinition()->fields()->create(['type' => FieldType::IMAGE, 'label' => 'Foto', 'required' => false, 'sort_order' => 0, 'max_images' => 1]);
    $source = $form->fields()->create(['type' => FieldType::IMAGE, 'label' => 'Foto', 'required' => false, 'sort_order' => 10, 'max_images' => 2]);
    $config->category()->associate($category)->save();
    $config->fieldMappings()->create(['target_field_id' => $target->id, 'source_field_id' => $source->id]);

    $this->post(route('form.submit', $form), [
        $config->titleField->uuid => 'Igeltor',
        $config->descriptionField->uuid => 'Am Tor',
        $config->coordinateField->uuid => ['lat' => 49.8728, 'lng' => 8.6510],
        $source->uuid => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')],
    ])->assertSessionHasNoErrors();

    $submissionPaths = $source->submissionFields()->sole()->value;
    $mapPoint = MapPoint::sole();
    $pointPaths = storedImagePaths($mapPoint, $target);

    expect($pointPaths)->toHaveCount(1)
        ->and($pointPaths[0])->toStartWith($mapPoint->imageDirectory().'/')
        ->and(Storage::disk('images')->get($pointPaths[0]))->toBe(Storage::disk('images')->get($submissionPaths[0]));

    $mapPoint->delete();
    Storage::disk('images')->assertExists($submissionPaths);
});

test('image fields are left out of spreadsheet exports and imports', function (): void {
    Excel::fake();
    $text = $this->category->formDefinition->fields()->create(['type' => FieldType::TEXT, 'label' => 'Notiz', 'required' => false, 'sort_order' => 1]);
    $this->actingAs(imageTestAdmin($this->group));

    $this->get(route('mappoints.import.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('fields', fn (Collection $fields): bool => $fields->pluck('form_field_id')->filter()->values()->all() === [$text->uuid]));

    $this->get(route('mappoints.export', ['format' => 'xlsx']))->assertOk();
    Excel::assertDownloaded('kartenpunkte-'.now()->format('Y-m-d').'.xlsx', fn (MapPointsExport $export): bool => ! in_array(MapPointSpreadsheetFieldData::categoryFieldLabel($this->photo), $export->headings(), true)
        && in_array(MapPointSpreadsheetFieldData::categoryFieldLabel($text), $export->headings(), true));
});
