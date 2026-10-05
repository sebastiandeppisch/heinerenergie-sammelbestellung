<?php

use App\Enums\FieldType;
use App\Models\Group;
use App\Models\MapPoint;
use App\Models\MapPointCategory;
use App\Models\User;
use App\Services\ImageStorage;
use App\Services\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('images');
    Storage::fake('image-cache');

    $user = User::factory()->create();
    $this->group = Group::factory()->create();
    $this->group->users()->attach($user, ['is_admin' => true]);
    app(SessionService::class)->actAsGroup($this->group, true);
    $this->actingAs($user);
});

test('a chosen image is previewed before saving', function (): void {
    $category = MapPointCategory::factory()->for($this->group)->create();
    $category->findOrCreateFormDefinition()->fields()->create(['type' => FieldType::IMAGE, 'label' => 'Foto vom Igeltor', 'sort_order' => 0, 'max_images' => 2]);
    $mapPoint = MapPoint::factory()->for($this->group)->create(['category_id' => $category->id]);
    $file = tempnam(sys_get_temp_dir(), 'photo').'.jpg';
    imagejpeg(imagecreatetruecolor(1200, 900), $file);

    visit(route('mappoints.edit', $mapPoint))
        ->attach('[data-test=map-point-image-file]', $file)
        ->assertCount('[data-test=map-point-images] img', 1)
        ->assertSee('1 / 2 Bilder')
        ->assertNoJavaScriptErrors();
});

test('stored images are shown in the detail row and can be removed', function (): void {
    $category = MapPointCategory::factory()->for($this->group)->create();
    $photo = $category->findOrCreateFormDefinition()->fields()->create(['type' => FieldType::IMAGE, 'label' => 'Foto vom Igeltor', 'sort_order' => 0, 'max_images' => 2]);
    $mapPoint = MapPoint::factory()->for($this->group)->create(['category_id' => $category->id]);
    $path = app(ImageStorage::class)->store(UploadedFile::fake()->image('photo.jpg', 1200, 900), $mapPoint->imageDirectory());
    $photo->createMapPointField($mapPoint, [$path]);

    visit(route('mappoints.index'))
        ->click('[data-test=toggle-point-details]')
        ->assertCount('[data-test=map-point-field-images] img', 1)
        ->click('[data-test=map-point-field-images] button')
        ->assertCount('[role=dialog] img', 1)
        ->assertNoJavaScriptErrors();

    visit(route('mappoints.edit', $mapPoint))
        ->assertCount('[data-test=map-point-images] img', 1)
        ->click('[aria-label="Bild entfernen"]')
        ->click('Kartenpunkt aktualisieren')
        ->assertSee('Der Kartenpunkt wurde aktualisiert')
        ->assertNoJavaScriptErrors();

    expect($mapPoint->fields()->count())->toBe(0);
    Storage::disk('images')->assertMissing($path);
});

test('a point with an empty image field can be edited and saved', function (): void {
    $category = MapPointCategory::factory()->for($this->group)->create();
    $category->findOrCreateFormDefinition()->fields()->create(['type' => FieldType::IMAGE, 'label' => 'Foto vom Igeltor', 'sort_order' => 0, 'max_images' => 2]);
    $mapPoint = MapPoint::factory()->for($this->group)->create(['category_id' => $category->id, 'title' => 'Igeltor']);

    visit(route('mappoints.edit', $mapPoint))
        ->assertCount('[data-test=map-point-images] img', 0)
        ->fill('title', 'Igeltor am Park')
        ->click('Kartenpunkt aktualisieren')
        ->assertSee('Der Kartenpunkt wurde aktualisiert')
        ->assertNoJavaScriptErrors();

    expect($mapPoint->refresh()->title)->toBe('Igeltor am Park')
        ->and($mapPoint->fields()->count())->toBe(0);
});
