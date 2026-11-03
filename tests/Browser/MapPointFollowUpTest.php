<?php

use App\Enums\FieldType;
use App\Models\FormSubmission;
use App\Models\Group;
use App\Models\MapEmbed;
use App\Models\MapPoint;
use App\Models\MapPointCategory;
use App\Services\FormDefinitionService;
use App\Services\FormSubmissionConfirmationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\AutoAttachesFormEmbedToken;

uses(RefreshDatabase::class, AutoAttachesFormEmbedToken::class);

test('after confirming, the person opens the follow-up form, picks a photo and it appears in the form and the popup', function (): void {
    Mail::fake();
    Storage::fake('images');
    Storage::fake('image-cache');

    $group = Group::factory()->create(['name' => 'Stadtnatur']);
    $category = MapPointCategory::factory()->for($group)->withoutImage()->create(['name' => 'Naturgarten']);
    $photo = $category->findOrCreateFormDefinition()->fields()->create(['type' => FieldType::IMAGE, 'label' => 'Das ist mein Garten', 'required' => false, 'sort_order' => 0, 'max_images' => 1]);
    $category->syncPublicFields([$photo->id]);

    $form = app(FormDefinitionService::class)->createFromTemplate('map_point', $group->uuid, $category, withFollowUp: true, followUpOnlyFieldUuids: [$photo->uuid]);
    $photoQuestion = $form->mapPointCreator->followUpFormDefinition->fields()->where('label', 'Das ist mein Garten')->sole();
    $form->update(['requires_email_confirmation' => true]);
    $fields = $form->fields()->get()->keyBy('label');
    $fields['E-Mail-Adresse']->update(['required' => true]);

    // Placing the pin depends on the map tiles, so the form is sent without the browser.
    $this->post(route('form.submit', $form), [
        $fields['Titel']->uuid => 'Garten Müller',
        $fields['Beschreibung']->uuid => 'Mit Teich',
        $fields['Standort']->uuid => ['lat' => 49.8728, 'lng' => 8.6510],
        $fields['E-Mail-Adresse']->uuid => 'erika@example.com',
        '_consent_show' => true,
        '_consent_details' => true,
    ])->assertSessionHasNoErrors();

    $image = sys_get_temp_dir().'/garten-'.uniqid().'.jpg';
    imagejpeg(imagecreatetruecolor(640, 480), $image);

    $page = visit(route('form.confirm.show', app(FormSubmissionConfirmationService::class)->issueToken(FormSubmission::sole())))
        ->click('E-Mail-Adresse bestätigen')
        ->assertSee('Dein Eintrag ist angelegt.')
        ->click('Erzähl mehr')
        ->assertSee('Erzähl mehr: Naturgarten')
        ->assertSee('Dein Eintrag „Garten Müller“')
        ->attach('input[type="file"]', $image)
        ->assertPresent('img[alt="Vorschau"]')
        ->assertNoJavaScriptErrors();

    // The HTTP server of the browser tests passes no uploaded files to the app, so the photo is sent without the browser.
    $token = basename(dirname((string) parse_url($page->url(), PHP_URL_PATH)));
    $this->post(route('map-point.manage.follow-up.submit', $token), [
        $photoQuestion->uuid => [UploadedFile::fake()->image('garten.jpg', 640, 480)],
    ])->assertSessionHasNoErrors();
    unlink($image);

    $mapPoint = MapPoint::sole();
    expect($mapPoint->fields()->where('form_field_id', $photo->id)->sole()->value)->toHaveCount(1);

    $page->navigate(route('map-point.manage.follow-up', $token))
        ->assertPresent('img[alt="Vorschau"][src*="/bilder/"]')
        ->assertPresent('[aria-label="Bild entfernen"]')
        ->assertNoJavaScriptErrors();

    $mapPoint->update(['published' => true]);
    $embed = MapEmbed::factory()->for($group)->create(['lat' => 49.8728, 'lng' => 8.6510, 'zoom' => 14]);
    $embed->mapPointCategories()->sync([$category->id]);

    visit(route('map.public', $embed))
        ->click('.leaflet-marker-icon')
        ->assertSeeIn('.leaflet-popup-content', 'Garten Müller')
        ->assertPresent('.leaflet-popup-content [data-test="map-point-field-images"] img')
        ->assertNoJavaScriptErrors();
});
