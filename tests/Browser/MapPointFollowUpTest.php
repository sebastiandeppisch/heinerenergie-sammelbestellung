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
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\AutoAttachesFormEmbedToken;

uses(RefreshDatabase::class, AutoAttachesFormEmbedToken::class);

test('after confirming, the person adds a photo with the follow-up form and it appears in the popup', function (): void {
    Mail::fake();
    Storage::fake('images');
    Storage::fake('image-cache');

    $group = Group::factory()->create(['name' => 'Stadtnatur']);
    $category = MapPointCategory::factory()->for($group)->withoutImage()->create(['name' => 'Naturgarten']);
    $photo = $category->findOrCreateFormDefinition()->fields()->create(['type' => FieldType::IMAGE, 'label' => 'Das ist mein Garten', 'required' => false, 'sort_order' => 0, 'max_images' => 1]);
    $category->syncPublicFields([$photo->id]);

    $form = app(FormDefinitionService::class)->createFromTemplate('map_point', $group->uuid, $category, withFollowUp: true, followUpOnlyFieldUuids: [$photo->uuid]);
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
        ->click('Absenden')
        ->wait(2);

    expect((string) $page->text('body'))->toContain('Danke, deine Angaben sind gespeichert.');
    $page->assertNoJavaScriptErrors();

    $mapPoint = MapPoint::sole();
    $submission = FormSubmission::where('map_point_id', $mapPoint->id)->sole();
    expect($submission->submissionFields()->where('label', 'Das ist mein Garten')->sole()->value)->toHaveCount(1)
        ->and($mapPoint->fields()->pluck('value', 'form_field_id')->all())->toHaveKey($photo->id);
    $mapPoint->update(['published' => true]);
    $embed = MapEmbed::factory()->for($group)->create(['lat' => 49.8728, 'lng' => 8.6510, 'zoom' => 14]);
    $embed->mapPointCategories()->sync([$category->id]);

    visit(route('map.public', $embed))
        ->click('.leaflet-marker-icon')
        ->assertSeeIn('.leaflet-popup-content', 'Garten Müller')
        ->assertPresent('.leaflet-popup-content [data-test="map-point-field-images"] img')
        ->assertNoJavaScriptErrors();

    unlink($image);
});
