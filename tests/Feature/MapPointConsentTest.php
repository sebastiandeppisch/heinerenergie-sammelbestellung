<?php

use App\Data\FormDefinitionData;
use App\Enums\FieldType;
use App\Enums\PublicConsent;
use App\Mail\FormSubmissionConfirmation;
use App\Models\FormDefinitionToMapPoint;
use App\Models\MapEmbed;
use App\Models\MapPoint;
use App\Models\MapPointCategory;
use App\Models\User;
use App\Services\MapPointConsentService;
use App\Services\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\AutoAttachesFormEmbedToken;

uses(RefreshDatabase::class, AutoAttachesFormEmbedToken::class);

beforeEach(function (): void {
    Mail::fake();
    Storage::fake('images');
    Storage::fake('image-cache');

    $this->config = FormDefinitionToMapPoint::factory()->create();
    $this->form = $this->config->formDefinition;
    $this->form->group->update(['name' => 'Gartenkreis']);

    $this->category = MapPointCategory::factory()->for($this->form->group)->create(['name' => 'Garten']);
    $categoryFields = $this->category->findOrCreateFormDefinition();
    $this->size = $categoryFields->fields()->create(['type' => FieldType::TEXT, 'label' => 'Größe', 'required' => false, 'sort_order' => 0]);
    $this->note = $categoryFields->fields()->create(['type' => FieldType::TEXT, 'label' => 'Zugang', 'required' => false, 'sort_order' => 1]);
    $this->photo = $categoryFields->fields()->create(['type' => FieldType::IMAGE, 'label' => 'Foto', 'required' => false, 'sort_order' => 2, 'max_images' => 1]);
    $this->category->syncPublicFields([$this->size->id, $this->photo->id]);

    $this->formSize = $this->form->fields()->create(['type' => FieldType::TEXT, 'label' => 'Größe', 'required' => false, 'sort_order' => 10]);
    $this->formNote = $this->form->fields()->create(['type' => FieldType::TEXT, 'label' => 'Zugang', 'required' => false, 'sort_order' => 11]);
    $this->formPhoto = $this->form->fields()->create(['type' => FieldType::IMAGE, 'label' => 'Foto', 'required' => false, 'sort_order' => 12, 'max_images' => 1]);
    $this->email = $this->form->fields()->create(['type' => FieldType::EMAIL, 'label' => 'E-Mail', 'required' => true, 'sort_order' => 13]);

    $this->config->category()->associate($this->category);
    $this->config->contactEmailField()->associate($this->email);
    $this->config->save();
    $this->config->fieldMappings()->createMany([
        ['target_field_id' => $this->size->id, 'source_field_id' => $this->formSize->id],
        ['target_field_id' => $this->note->id, 'source_field_id' => $this->formNote->id],
        ['target_field_id' => $this->photo->id, 'source_field_id' => $this->formPhoto->id],
    ]);

    $this->embed = MapEmbed::factory()->for($this->form->group)->create();
    $this->embed->mapPointCategories()->sync([$this->category->id]);
});

/**
 * @param  array<string, mixed>  $consent
 * @return TestResponse<Response>
 */
function submitConsentForm(array $consent = []): TestResponse
{
    $test = test();

    return $test->post(route('form.submit', $test->form), [
        $test->config->titleField->uuid => 'Naturgarten Müller',
        $test->config->descriptionField->uuid => 'Mit Teich',
        $test->config->coordinateField->uuid => ['lat' => 49.8728, 'lng' => 8.6510],
        $test->formSize->uuid => '200 m²',
        $test->formNote->uuid => 'Schlüssel unter der Matte',
        $test->formPhoto->uuid => [UploadedFile::fake()->image('teich.jpg')],
        $test->email->uuid => 'erika@example.com',
        ...$consent,
    ]);
}

function expectedConsentText(): string
{
    return strtr(MapPointConsentService::DEFAULT_TEXT, ['{initiative}' => 'Gartenkreis', '{felder}' => 'Titel, Beschreibung, Größe, Foto']);
}

function publishedConsentPoint(): MapPoint
{
    $mapPoint = MapPoint::sole();
    $mapPoint->update(['published' => true, 'location' => 'Musterstraße 1, 64283 Darmstadt']);

    return $mapPoint;
}

test('the choice in the consent block decides the consent of the point', function (array $consent, PublicConsent $expected, ?array $expectedKeys, bool $hasText): void {
    submitConsentForm($consent)->assertSessionHasNoErrors();

    $mapPoint = MapPoint::sole();
    $fieldKeys = $expectedKeys === null ? null : array_map(fn (string $key): string => match ($key) {
        'size' => 'field:'.test()->size->uuid,
        'photo' => 'field:'.test()->photo->uuid,
        default => $key,
    }, $expectedKeys);

    expect($mapPoint->public_consent)->toBe($expected)
        ->and($mapPoint->public_consent_fields)->toBe($fieldKeys)
        ->and($mapPoint->public_consent_text)->toBe($hasText ? expectedConsentText() : null)
        ->and($mapPoint->public_consent_at)->not->toBeNull()
        ->and($mapPoint->contact_email)->toBe('erika@example.com');
})->with([
    'nothing chosen' => [[], PublicConsent::REFUSED, null, false],
    'details without the point' => [['_consent_details' => true], PublicConsent::REFUSED, null, false],
    'only the point' => [['_consent_show' => true], PublicConsent::GIVEN, ['coordinate'], true],
    'the point and the details' => [['_consent_show' => true, '_consent_details' => true], PublicConsent::GIVEN, ['coordinate', 'title', 'description', 'size', 'photo'], true],
]);

test('the public form offers the consent block with the labels of the public fields', function (): void {
    $this->get(route('form.show', $this->form))
        ->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page
            ->component('Forms/Show')
            ->where('formDefinition.map_point_consent.text', strtr(MapPointConsentService::DEFAULT_TEXT, ['{initiative}' => 'Gartenkreis']))
            ->where('formDefinition.map_point_consent.base_labels', ['Titel', 'Beschreibung'])
            ->where('formDefinition.map_point_consent.default_category_labels', ['Größe', 'Foto'])
        );
});

test('the consent is frozen when the form is submitted', function (): void {
    $this->form->update(['requires_email_confirmation' => true]);
    submitConsentForm(['_consent_show' => true, '_consent_details' => true])->assertSessionHasNoErrors();
    $token = basename(parse_url(Mail::queued(FormSubmissionConfirmation::class)->last()->confirmationUrl, PHP_URL_PATH));

    $this->config->update(['consent_text' => 'Neuer Text für {felder}']);
    $this->category->syncPublicFields([$this->size->id, $this->note->id, $this->photo->id]);
    $this->post(route('form.confirm', $token))->assertOk();

    $mapPoint = MapPoint::sole();
    expect($mapPoint->public_consent_text)->toBe(expectedConsentText())
        ->and($mapPoint->public_consent_fields)->toBe(['coordinate', 'title', 'description', 'field:'.$this->size->uuid, 'field:'.$this->photo->uuid]);
});

test('a field made public later stays private for a given consent, a field made private disappears at once', function (): void {
    submitConsentForm(['_consent_show' => true, '_consent_details' => true]);
    publishedConsentPoint();
    $points = "pointsByCategory.{$this->category->uuid}";

    $this->category->syncPublicFields([$this->size->id, $this->note->id, $this->photo->id]);
    $this->get(route('map.public', $this->embed))
        ->assertInertia(fn (Assert $page): Assert => $page
            ->has("{$points}.0.fields", 2)
            ->where("{$points}.0.fields.0.label", 'Größe')
            ->where("{$points}.0.fields.1.label", 'Foto')
        )
        ->assertDontSee('Schlüssel unter der Matte');

    $this->category->syncPublicFields([$this->photo->id]);
    $this->get(route('map.public', $this->embed))
        ->assertInertia(fn (Assert $page): Assert => $page
            ->has("{$points}.0.fields", 1)
            ->where("{$points}.0.fields.0.label", 'Foto')
        );
});

test('without consent to the details the category replaces the title and description and location are left out', function (): void {
    submitConsentForm(['_consent_show' => true]);
    publishedConsentPoint();
    $points = "pointsByCategory.{$this->category->uuid}";

    $this->get(route('map.public', $this->embed))
        ->assertInertia(fn (Assert $page): Assert => $page
            ->has($points, 1)
            ->where("{$points}.0.title", 'Garten')
            ->where("{$points}.0.description", '')
            ->where("{$points}.0.location", null)
            ->has("{$points}.0.fields", 0)
        )
        ->assertDontSee('Naturgarten Müller')
        ->assertDontSee('Mit Teich')
        ->assertDontSee('Musterstraße');
});

test('with consent to the details title and description are shown, the location is never consented to by a form', function (): void {
    submitConsentForm(['_consent_show' => true, '_consent_details' => true]);
    publishedConsentPoint();
    $points = "pointsByCategory.{$this->category->uuid}";

    $this->get(route('map.public', $this->embed))
        ->assertInertia(fn (Assert $page): Assert => $page
            ->where("{$points}.0.title", 'Naturgarten Müller')
            ->where("{$points}.0.description", 'Mit Teich')
            ->where("{$points}.0.location", null)
        );
});

test('a refused point appears in no public output', function (): void {
    submitConsentForm();
    $mapPoint = publishedConsentPoint();
    MapPoint::factory()->for($this->form->group)->create(['published' => true, 'category_id' => $this->category->id, 'title' => 'Bestandspunkt']);
    $photoPath = $mapPoint->fields()->where('form_field_id', $this->photo->id)->sole()->value[0];
    $points = "pointsByCategory.{$this->category->uuid}";

    $this->get(route('map.public', $this->embed))
        ->assertInertia(fn (Assert $page): Assert => $page->has($points, 1)->where("{$points}.0.title", 'Bestandspunkt'))
        ->assertDontSee('Naturgarten Müller');

    $this->get(route('map-point-images.show', [$mapPoint, basename($photoPath)]))->assertNotFound();

    $admin = User::factory()->create(['is_admin' => true]);
    app(SessionService::class)->actAsGroup($this->form->group);
    Config::set('app.group_context', 'global');
    $this->actingAs($admin)
        ->get(route('map-embeds.create'))
        ->assertInertia(fn (Assert $page): Assert => $page->has($points, 1)->where("{$points}.0.title", 'Bestandspunkt'));
});

test('an image is served publicly only with consent to its field', function (): void {
    submitConsentForm(['_consent_show' => true]);
    $mapPoint = publishedConsentPoint();
    $url = route('map-point-images.show', [$mapPoint, basename($mapPoint->fields()->where('form_field_id', $this->photo->id)->sole()->value[0])]);

    $this->get($url)->assertNotFound();

    $mapPoint->update(['public_consent_fields' => ['coordinate', 'field:'.$this->photo->uuid]]);
    $this->get($url)->assertOk();
});

test('the contact address is never part of a public payload', function (): void {
    submitConsentForm(['_consent_show' => true, '_consent_details' => true]);
    publishedConsentPoint();
    MapPoint::factory()->for($this->form->group)->create(['published' => true, 'category_id' => $this->category->id, 'contact_email' => 'bestand@example.com']);

    $this->get(route('map.public', $this->embed))
        ->assertOk()
        ->assertSee('Naturgarten Müller')
        ->assertDontSee('erika@example.com')
        ->assertDontSee('bestand@example.com')
        ->assertDontSee('contact_email');

    Auth::logout();
    $this->get(route('form.show', $this->form))->assertDontSee('erika@example.com');
});

test('the migrations keep existing points as they are and fill the consent text of existing targets', function (): void {
    $mapPoint = MapPoint::factory()->create(['published' => true]);
    $pointMigration = require database_path('migrations/2026_10_19_120000_add_public_consent_to_map_points.php');
    $targetMigration = require database_path('migrations/2026_10_19_120100_add_consent_to_form_definition_to_map_points.php');

    $targetMigration->down();
    $pointMigration->down();
    $pointMigration->up();
    $targetMigration->up();

    expect($mapPoint->fresh()->public_consent)->toBe(PublicConsent::NOT_REQUIRED)
        ->and($this->config->fresh()->consent_text)->toBe(MapPointConsentService::DEFAULT_TEXT);
});

test('points created by admins need no consent', function (): void {
    expect(MapPoint::factory()->create()->fresh()->public_consent)->toBe(PublicConsent::NOT_REQUIRED);
});

test('an admin may refuse a consent but never give one', function (): void {
    submitConsentForm(['_consent_show' => true]);
    $mapPoint = MapPoint::sole();
    $admin = User::factory()->create();
    $this->form->group->users()->attach($admin, ['is_admin' => true]);
    app(SessionService::class)->actAsGroup($this->form->group, true);

    $this->actingAs($admin)->post(route('mappoints.refuse-consent', $mapPoint))->assertRedirect();
    expect($mapPoint->fresh()->public_consent)->toBe(PublicConsent::REFUSED);

    $this->actingAs($admin)->patch(route('mappoints.update-contact-email', $mapPoint), ['contact_email' => 'neu@example.com'])->assertRedirect();
    expect($mapPoint->fresh()->contact_email)->toBe('neu@example.com');

    $this->actingAs($admin)
        ->get(route('mappoints.edit', $mapPoint))
        ->assertInertia(fn (Assert $page): Assert => $page
            ->where('consent.public_consent', 'refused')
            ->where('consent.contact_email', 'neu@example.com')
            ->where('consent.public_consent_text', expectedConsentText())
            ->where('consent.submission_id', $mapPoint->pointable->uuid)
        );
});

test('the form builder saves the consent text and the contact field', function (): void {
    $admin = User::factory()->create();
    $this->form->group->users()->attach($admin, ['is_admin' => true]);
    app(SessionService::class)->actAsGroup($this->form->group, true);
    $payload = FormDefinitionData::fromModel($this->form->fresh()->load('fields.options'))->toArray();
    $payload['map_point_mapping']['consent_text'] = 'Karte von {initiative}: {felder}';
    $payload['map_point_mapping']['contact_email_field_id'] = null;

    $this->actingAs($admin)->put(route('form-definitions.update', $this->form), $payload)->assertSessionHasNoErrors();

    $config = $this->config->fresh();
    expect($config->consent_text)->toBe('Karte von {initiative}: {felder}')
        ->and($config->contact_email_field_id)->toBeNull();

    $payload['map_point_mapping']['consent_text'] = '';
    $payload['map_point_mapping']['contact_email_field_id'] = $this->formSize->uuid;
    $this->actingAs($admin)->put(route('form-definitions.update', $this->form), $payload)
        ->assertSessionHasErrors(['map_point_mapping.consent_text', 'map_point_mapping.contact_email_field_id']);
});
