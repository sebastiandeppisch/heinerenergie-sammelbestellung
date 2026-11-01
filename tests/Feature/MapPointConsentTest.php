<?php

use App\Data\FormDefinitionData;
use App\Enums\FieldType;
use App\Enums\PublicConsent;
use App\Mail\FormSubmissionConfirmation;
use App\Models\FormDefinitionToMapPoint;
use App\Models\FormField;
use App\Models\FormSubmission;
use App\Models\Group;
use App\Models\MapEmbed;
use App\Models\MapPoint;
use App\Models\MapPointCategory;
use App\Models\User;
use App\Services\MapPointConsentService;
use App\Services\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
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
function submitConsentForm(FormDefinitionToMapPoint $config, array $consent = []): TestResponse
{
    $fields = $config->formDefinition->fields()->get()->keyBy('label');

    return test()->post(route('form.submit', $config->formDefinition), [
        $config->titleField->uuid => 'Naturgarten Müller',
        $config->descriptionField->uuid => 'Mit Teich',
        $config->coordinateField->uuid => ['lat' => 49.8728, 'lng' => 8.6510],
        $fields['Größe']->uuid => '200 m²',
        $fields['Zugang']->uuid => 'Schlüssel unter der Matte',
        $fields['Foto']->uuid => [UploadedFile::fake()->image('teich.jpg')],
        $fields['E-Mail']->uuid => 'erika@example.com',
        ...$consent,
    ]);
}

function expectedConsentText(bool $details = true, string $labels = 'Titel, Beschreibung, Größe, Foto'): string
{
    return $details
        ? strtr(MapPointConsentService::DEFAULT_DETAILS_TEXT, ['{initiative}' => 'Gartenkreis', '{felder}' => $labels, '{widerruf}' => MapPointConsentService::REVOCATION_BY_MAIL])
        : strtr(MapPointConsentService::DEFAULT_POINT_TEXT, ['{initiative}' => 'Gartenkreis', '{widerruf}' => MapPointConsentService::REVOCATION_BY_MAIL]);
}

function mapLocationField(FormDefinitionToMapPoint $config): FormField
{
    $field = $config->formDefinition->fields()->create(['type' => FieldType::TEXT, 'label' => 'Ortsbeschreibung', 'required' => false, 'max_length' => 255, 'sort_order' => 14]);
    $config->locationField()->associate($field)->save();

    return $field;
}

function consentGroupAdmin(Group $group): User
{
    $admin = User::factory()->create();
    $group->users()->attach($admin, ['is_admin' => true]);
    app(SessionService::class)->actAsGroup($group, true);

    return $admin;
}

function publishedConsentPoint(): MapPoint
{
    $mapPoint = MapPoint::sole();
    $mapPoint->update(['published' => true, 'location' => 'Musterstraße 1, 64283 Darmstadt']);

    return $mapPoint;
}

test('the choice in the consent block decides the consent of the point', function (array $consent, PublicConsent $expected, ?array $expectedKeys, ?string $level): void {
    submitConsentForm($this->config, $consent)->assertSessionHasNoErrors();

    $mapPoint = MapPoint::sole();
    $fieldKeys = $expectedKeys === null ? null : array_map(fn (string $key): string => match ($key) {
        'size' => 'field:'.$this->size->uuid,
        'photo' => 'field:'.$this->photo->uuid,
        default => $key,
    }, $expectedKeys);

    expect($mapPoint->public_consent)->toBe($expected)
        ->and($mapPoint->public_consent_fields)->toBe($fieldKeys)
        ->and($mapPoint->public_consent_text)->toBe($level === null ? null : expectedConsentText($level === 'details'))
        ->and($mapPoint->public_consent_at)->not->toBeNull()
        ->and($mapPoint->contact_email)->toBe('erika@example.com');
})->with([
    'nothing chosen' => [[], PublicConsent::REFUSED, null, null],
    'details without the point' => [['_consent_details' => true], PublicConsent::REFUSED, null, null],
    'only the point' => [['_consent_show' => true], PublicConsent::GIVEN, ['coordinate'], 'point'],
    'the point and the details' => [['_consent_show' => true, '_consent_details' => true], PublicConsent::GIVEN, ['coordinate', 'title', 'description', 'size', 'photo'], 'details'],
]);

test('the public form offers the consent block with the labels of the public fields', function (): void {
    $this->get(route('form.show', $this->form))
        ->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page
            ->component('Forms/Show')
            ->where('formDefinition.map_point_consent.point_text', strtr(MapPointConsentService::DEFAULT_POINT_TEXT, ['{initiative}' => 'Gartenkreis']))
            ->where('formDefinition.map_point_consent.details_text', strtr(MapPointConsentService::DEFAULT_DETAILS_TEXT, ['{initiative}' => 'Gartenkreis']))
            ->where('formDefinition.map_point_consent.revocation_text', MapPointConsentService::REVOCATION_BY_MAIL)
            ->where('formDefinition.map_point_consent.revocation_url', null)
            ->where('formDefinition.map_point_consent.base_labels', ['Titel', 'Beschreibung'])
            ->where('formDefinition.map_point_consent.default_category_labels', ['Größe', 'Foto'])
        );
});

test('the consent is frozen when the form is submitted', function (): void {
    $this->form->update(['requires_email_confirmation' => true]);
    submitConsentForm($this->config, ['_consent_show' => true, '_consent_details' => true])->assertSessionHasNoErrors();
    $token = basename(parse_url(Mail::queued(FormSubmissionConfirmation::class)->last()->confirmationUrl, PHP_URL_PATH));

    $this->config->forceFill(['consent_text' => 'Neuer Text für {felder}'])->save();
    $this->category->syncPublicFields([$this->size->id, $this->note->id, $this->photo->id]);
    $this->post(route('form.confirm', $token))->assertOk();

    $mapPoint = MapPoint::sole();
    expect($mapPoint->public_consent_text)->toBe(expectedConsentText())
        ->and($mapPoint->public_consent_fields)->toBe(['coordinate', 'title', 'description', 'field:'.$this->size->uuid, 'field:'.$this->photo->uuid]);
});

test('a field made public later stays private for a given consent, a field made private disappears at once', function (): void {
    submitConsentForm($this->config, ['_consent_show' => true, '_consent_details' => true]);
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
    submitConsentForm($this->config, ['_consent_show' => true]);
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

test('with consent to the details title and description are shown, a location an admin added stays private', function (): void {
    submitConsentForm($this->config, ['_consent_show' => true, '_consent_details' => true]);
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
    submitConsentForm($this->config);
    $mapPoint = publishedConsentPoint();
    MapPoint::factory()->for($this->form->group)->create(['published' => true, 'category_id' => $this->category->id, 'title' => 'Bestandspunkt']);
    $photoPath = $mapPoint->fields()->where('form_field_id', $this->photo->id)->sole()->value[0];
    $points = "pointsByCategory.{$this->category->uuid}";

    $this->get(route('map.public', $this->embed))
        ->assertInertia(fn (Assert $page): Assert => $page->has($points, 1)->where("{$points}.0.title", 'Bestandspunkt'))
        ->assertDontSee('Naturgarten Müller');

    $this->get(route('map-point-images.show', [$mapPoint, basename($photoPath)]))->assertNotFound();

    $this->actingAs(consentGroupAdmin($this->form->group))
        ->get(route('map-embeds.create'))
        ->assertInertia(fn (Assert $page): Assert => $page->has($points, 1)->where("{$points}.0.title", 'Bestandspunkt'));
});

test('an image is served publicly only with consent to its field', function (): void {
    submitConsentForm($this->config, ['_consent_show' => true]);
    $mapPoint = publishedConsentPoint();
    $url = route('map-point-images.show', [$mapPoint, basename($mapPoint->fields()->where('form_field_id', $this->photo->id)->sole()->value[0])]);

    $this->get($url)->assertNotFound();

    $mapPoint->update(['public_consent_fields' => ['coordinate', 'field:'.$this->photo->uuid]]);
    $this->get($url)->assertOk();
});

test('the contact address is never part of a public payload', function (): void {
    submitConsentForm($this->config, ['_consent_show' => true, '_consent_details' => true]);
    publishedConsentPoint();
    MapPoint::factory()->for($this->form->group)->create(['published' => true, 'category_id' => $this->category->id, 'contact_email' => 'bestand@example.com']);

    $page = json_encode($this->get(route('map.public', $this->embed))->assertOk()->viewData('page'));

    expect($page)->toContain('Naturgarten')
        ->not->toContain('erika@example.com')
        ->not->toContain('bestand@example.com')
        ->not->toContain('contact_email');
});

test('existing points need no consent and existing targets get the new default texts, own texts stay', function (): void {
    $default = collect(Schema::getColumns('map_points'))->firstWhere('name', 'public_consent')['default'];
    $oldDefault = 'Ich bin einverstanden, dass mein Eintrag auf der Karte von {initiative} öffentlich angezeigt wird, mit folgenden Angaben: {felder} sowie dem Standort. Ich kann meine Einwilligung jederzeit über den Link in der Bestätigungsmail widerrufen.';
    $ownText = FormDefinitionToMapPoint::factory()->create();
    DB::table('form_definition_to_map_points')->update(['consent_text' => $oldDefault, 'consent_point_text' => null]);
    DB::table('form_definition_to_map_points')->where('id', $ownText->id)->update(['consent_text' => 'Eigener Text mit {felder}']);

    (require database_path('migrations/2026_10_19_120100_add_consent_to_form_definition_to_map_points.php'))->fillConsentTexts();
    (require database_path('migrations/2026_11_01_100000_add_consent_levels_and_location_to_form_definition_to_map_points.php'))->updateTexts();

    expect($default)->toContain('not_required')
        ->and($this->config->fresh()->consent_text)->toBe(MapPointConsentService::DEFAULT_DETAILS_TEXT)
        ->and($this->config->fresh()->consent_point_text)->toBe(MapPointConsentService::DEFAULT_POINT_TEXT)
        ->and($ownText->fresh()->consent_text)->toBe('Eigener Text mit {felder}')
        ->and($ownText->fresh()->consent_point_text)->toBe(MapPointConsentService::DEFAULT_POINT_TEXT);
});

test('points created by admins need no consent', function (): void {
    expect(MapPoint::factory()->create()->fresh()->public_consent)->toBe(PublicConsent::NOT_REQUIRED);
});

test('an admin may refuse a consent but never give one', function (): void {
    submitConsentForm($this->config, ['_consent_show' => true]);
    $mapPoint = MapPoint::sole();
    $admin = consentGroupAdmin($this->form->group);

    $this->actingAs($admin)->post(route('mappoints.refuse-consent', $mapPoint))->assertRedirect();
    expect($mapPoint->fresh()->public_consent)->toBe(PublicConsent::REFUSED);

    // Admins edit the contact address with the point, see MapPointContactVerificationTest.
    $mapPoint->update(['contact_email' => 'neu@example.com']);

    $this->actingAs($admin)
        ->get(route('mappoints.edit', $mapPoint))
        ->assertInertia(fn (Assert $page): Assert => $page
            ->where('consent.public_consent', 'refused')
            ->where('consent.contact_email', 'neu@example.com')
            ->where('consent.public_consent_text', expectedConsentText(details: false))
            ->where('consent.submission_id', FormSubmission::sole()->uuid)
        );
});

test('the form builder saves the consent text and the contact field', function (): void {
    $admin = consentGroupAdmin($this->form->group);
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

test('the first level stores its own text without any field labels', function (): void {
    submitConsentForm($this->config, ['_consent_show' => true]);

    expect(MapPoint::sole()->public_consent_text)->toBe(expectedConsentText(details: false))
        ->not->toContain('Größe')
        ->not->toContain('Titel');
});

test('the second level stores exactly the listed keys, also of fields left empty', function (): void {
    $this->post(route('form.submit', $this->form), [
        $this->config->titleField->uuid => 'Naturgarten Müller',
        $this->config->coordinateField->uuid => ['lat' => 49.8728, 'lng' => 8.6510],
        $this->email->uuid => 'erika@example.com',
        '_consent_show' => true,
        '_consent_details' => true,
    ])->assertSessionHasNoErrors();

    $mapPoint = MapPoint::sole();
    expect($mapPoint->public_consent_fields)->toBe(['coordinate', 'title', 'description', 'field:'.$this->size->uuid, 'field:'.$this->photo->uuid])
        ->and($mapPoint->public_consent_text)->toBe(expectedConsentText());
});

test('without an email field the revocation points to the privacy policy', function (): void {
    $this->config->contactEmailField()->dissociate()->save();

    $this->get(route('form.show', $this->form))
        ->assertInertia(fn (Assert $page): Assert => $page
            ->where('formDefinition.map_point_consent.revocation_text', MapPointConsentService::REVOCATION_BY_POLICY)
            ->where('formDefinition.map_point_consent.revocation_url', route('datapolicy'))
        );

    submitConsentForm($this->config, ['_consent_show' => true]);

    expect(MapPoint::sole()->public_consent_text)->toBe(strtr(MapPointConsentService::DEFAULT_POINT_TEXT, [
        '{initiative}' => 'Gartenkreis',
        '{widerruf}' => MapPointConsentService::REVOCATION_BY_POLICY.' ('.route('datapolicy').')',
    ]));
});

test('a filled in location description is listed and consented to with the details', function (): void {
    $location = mapLocationField($this->config);

    submitConsentForm($this->config, ['_consent_show' => true, '_consent_details' => true, $location->uuid => 'Im Herrngarten am südlichen Brunnen']);

    $mapPoint = MapPoint::sole();
    expect($mapPoint->location)->toBe('Im Herrngarten am südlichen Brunnen')
        ->and($mapPoint->public_consent_fields)->toBe(['coordinate', 'title', 'description', 'location', 'field:'.$this->size->uuid, 'field:'.$this->photo->uuid])
        ->and($mapPoint->public_consent_text)->toBe(expectedConsentText(labels: 'Titel, Beschreibung, Ortsbeschreibung, Größe, Foto'));

    $mapPoint->update(['published' => true]);
    $this->get(route('map.public', $this->embed))
        ->assertInertia(fn (Assert $page): Assert => $page->where("pointsByCategory.{$this->category->uuid}.0.location", 'Im Herrngarten am südlichen Brunnen'));
});

test('an empty location description is not consented to, so a location an admin adds later stays private', function (): void {
    $location = mapLocationField($this->config);

    submitConsentForm($this->config, ['_consent_show' => true, '_consent_details' => true, $location->uuid => '']);

    $mapPoint = MapPoint::sole();
    expect($mapPoint->location)->toBeNull()
        ->and($mapPoint->public_consent_fields)->not->toContain('location')
        ->and($mapPoint->public_consent_text)->toBe(expectedConsentText());

    $mapPoint->update(['published' => true, 'location' => 'Musterstraße 1, 64283 Darmstadt']);
    $this->get(route('map.public', $this->embed))
        ->assertInertia(fn (Assert $page): Assert => $page->where("pointsByCategory.{$this->category->uuid}.0.location", null))
        ->assertDontSee('Musterstraße');

    $this->actingAs(consentGroupAdmin($this->form->group))
        ->get(route('mappoints.edit', $mapPoint))
        ->assertInertia(fn (Assert $page): Assert => $page->where('consent.location_consented', false));
});

test('the form builder saves both consent texts and the location field and checks them', function (): void {
    $admin = consentGroupAdmin($this->form->group);
    $location = mapLocationField($this->config);
    $payload = FormDefinitionData::fromModel($this->form->fresh()->load('fields.options'))->toArray();
    $payload['map_point_mapping']['consent_point_text'] = 'Karte von {initiative}. {widerruf}';
    $payload['map_point_mapping']['location_field_id'] = $location->uuid;

    $this->actingAs($admin)->put(route('form-definitions.update', $this->form), $payload)->assertSessionHasNoErrors();

    $config = $this->config->fresh();
    expect($config->consent_point_text)->toBe('Karte von {initiative}. {widerruf}')
        ->and($config->location_field_id)->toBe($location->id);

    $payload['map_point_mapping']['consent_point_text'] = 'Karte mit {felder}';
    $payload['map_point_mapping']['location_field_id'] = $this->email->uuid;
    $this->actingAs($admin)->put(route('form-definitions.update', $this->form), $payload)
        ->assertSessionHasErrors(['map_point_mapping.consent_point_text', 'map_point_mapping.location_field_id']);

    $payload['map_point_mapping']['consent_point_text'] = 'Karte von {initiative}. {widerruf}';
    $payload['map_point_mapping']['location_field_id'] = $location->uuid;
    $payload['fields'] = array_map(fn (array $field): array => $field['id'] === $location->uuid ? [...$field, 'max_length' => null] : $field, $payload['fields']);
    $this->actingAs($admin)->put(route('form-definitions.update', $this->form), $payload)
        ->assertSessionHasErrors(['map_point_mapping.location_field_id']);
});

test('a location description longer than the column is cut instead of failing the point', function (): void {
    $location = mapLocationField($this->config);
    $location->update(['max_length' => null]);

    submitConsentForm($this->config, ['_consent_show' => true, $location->uuid => str_repeat('a', 300)])->assertSessionHasNoErrors();

    expect(mb_strlen(MapPoint::sole()->location))->toBe(MapPoint::LOCATION_MAX_LENGTH);
});
