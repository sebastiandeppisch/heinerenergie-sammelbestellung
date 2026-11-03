<?php

use App\Data\FormDefinitionData;
use App\Enums\FieldType;
use App\Enums\MapPointTargetMode;
use App\Enums\PublicConsent;
use App\Mail\FormSubmissionConfirmation;
use App\Mail\MapPointCreated;
use App\Models\Advice;
use App\Models\FormDefinition;
use App\Models\FormField;
use App\Models\FormSubmission;
use App\Models\Group;
use App\Models\MapEmbed;
use App\Models\MapPoint;
use App\Models\MapPointCategory;
use App\Models\MapPointCharacteristic;
use App\Models\User;
use App\Services\FormDefinitionService;
use App\Services\MapPointEditLinkService;
use App\Services\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
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

    $this->group = Group::factory()->create(['name' => 'Stadtnatur']);
    $this->category = MapPointCategory::factory()->for($this->group)->create(['name' => 'Garten']);
    $categoryFields = $this->category->findOrCreateFormDefinition();
    $this->size = $categoryFields->fields()->create(['type' => FieldType::TEXT, 'label' => 'Größe', 'required' => false, 'sort_order' => 0]);
    $this->kind = $categoryFields->fields()->create(['type' => FieldType::SELECT, 'label' => 'Art', 'required' => false, 'sort_order' => 1]);
    $this->kind->options()->createMany([
        ['value' => 'wiese', 'label' => 'Wiese', 'sort_order' => 0],
        ['value' => 'teich', 'label' => 'Teich', 'sort_order' => 1],
    ]);
    $this->photo = $categoryFields->fields()->create(['type' => FieldType::IMAGE, 'label' => 'Foto', 'required' => false, 'sort_order' => 2, 'max_images' => 2]);
    $this->hedgehogGate = MapPointCharacteristic::factory()->for($this->category, 'category')->create(['name' => 'Igeltor']);
    $this->width = $this->hedgehogGate->findOrCreateFormDefinition()->fields()->create(['type' => FieldType::NUMBER, 'label' => 'Breite', 'required' => false, 'sort_order' => 0]);
    $this->category->syncPublicFields([$this->size->id, $this->kind->id, $this->photo->id]);
    $this->hedgehogGate->syncPublicFields([$this->width->id]);

    $this->form = app(FormDefinitionService::class)->createFromTemplate('map_point', $this->group->uuid, $this->category, withFollowUp: true, followUpOnlyFieldUuids: [$this->photo->uuid]);
    $this->followUp = $this->form->mapPointCreator->followUpFormDefinition;
});

/**
 * @param  array<string, mixed>  $values  keyed by the label of the form field
 * @return TestResponse<Response>
 */
function followUpSubmitFirstForm(FormDefinition $form, array $values = [], ?string $email = 'erika@example.org', bool $details = true): TestResponse
{
    $fields = $form->fields()->get()->keyBy('label');
    $data = [
        $fields['Titel']->uuid => 'Naturgarten Müller',
        $fields['Beschreibung']->uuid => 'Mit Teich',
        $fields['Standort']->uuid => ['lat' => 49.8728, 'lng' => 8.6510],
        $fields['E-Mail-Adresse']->uuid => $email,
        '_consent_show' => true,
        '_consent_details' => $details,
    ];

    foreach ($values as $label => $value) {
        $data[$fields[$label]->uuid] = $value;
    }

    return test()->post(route('form.submit', $form), $data);
}

/**
 * @param  array<string, mixed>  $values  keyed by the label of the form field
 * @param  array<string, mixed>  $extra
 * @return TestResponse<Response>
 */
function followUpSubmit(FormDefinition $followUp, string $token, array $values = [], array $extra = []): TestResponse
{
    $fields = $followUp->fields()->get()->keyBy('label');
    $data = $extra;

    foreach ($values as $label => $value) {
        $data[$fields[$label]->uuid] = $value;
    }

    return test()->post(route('map-point.manage.follow-up.submit', $token), $data);
}

/**
 * @param  TestResponse<Response>  $response
 */
function followUpTokenFromNotice(TestResponse $response): string
{
    $url = $response->viewData('page')['props']['notices'][0]['url'];

    return basename(dirname((string) parse_url($url, PHP_URL_PATH)));
}

function followUpFieldUuid(FormDefinition $form, string $label): string
{
    return $form->fields()->where('label', $label)->sole()->uuid;
}

/**
 * @return array<int, string>
 */
function followUpImagePaths(MapPoint $mapPoint, FormField $field): array
{
    return (array) $mapPoint->fields()->where('form_field_id', $field->id)->first()?->value;
}

function followUpAdmin(Group $group): User
{
    $admin = User::factory()->create();
    $group->users()->attach($admin, ['is_admin' => true]);
    app(SessionService::class)->actAsGroup($group, true);
    Config::set('app.group_context', 'global');

    return $admin;
}

test('the wizard creates a linked follow-up form that asks for all fields, the first form leaves out the chosen ones', function (): void {
    $creator = $this->form->mapPointCreator;
    $followUpCreator = $this->followUp->mapPointCreator;

    expect($creator->mode)->toBe(MapPointTargetMode::CREATE)
        ->and($creator->follow_up_button_text)->toBe('Erzähl mehr')
        ->and($this->form->fields()->pluck('label')->all())->not->toContain('Foto')
        ->and($followUpCreator->mode)->toBe(MapPointTargetMode::UPDATE)
        ->and($followUpCreator->map_point_category_id)->toBe($this->category->id)
        ->and($followUpCreator->title_field_id)->toBeNull()
        ->and($this->followUp->fields()->pluck('label')->all())->toBe(['Größe', 'Art', 'Foto', 'Maßnahmen', 'Igeltor: Breite'])
        ->and($followUpCreator->fieldMappings()->count())->toBe(4)
        ->and($this->followUp->fields()->where('label', 'Foto')->sole()->max_images)->toBe(2);
});

test('a follow-up form is not reachable as a public form', function (): void {
    $this->get(route('form.show', $this->followUp))->assertNotFound();
    $this->post(route('form.submit', $this->followUp), [])->assertNotFound();
});

test('the mail link and the follow-up button carry the same token, both work', function (): void {
    $response = followUpSubmitFirstForm($this->form)->assertSessionHasNoErrors();

    $response->assertInertia(fn (Assert $page): Assert => $page->component('Forms/Submitted')
        ->where('notices.0.title', 'Dein Eintrag ist angelegt.')
        ->where('notices.0.url_label', 'Erzähl mehr'));
    $token = followUpTokenFromNotice($response);
    $mailUrl = Mail::queued(MapPointCreated::class)->sole()->manageUrl;

    $this->get($mailUrl)->assertInertia(fn (Assert $page): Assert => $page->component('MapPointManage/Manage')->where('mapPoint.follow_up_button_text', 'Erzähl mehr'));
    $this->get(route('map-point.manage.follow-up', $token))->assertInertia(fn (Assert $page): Assert => $page->component('MapPointManage/FollowUp'));
    expect(basename((string) parse_url($mailUrl, PHP_URL_PATH)))->toBe($token);
});

test('after the confirmation the page shows the follow-up button with the token of the mail', function (): void {
    $this->form->update(['requires_email_confirmation' => true]);
    $this->form->fields()->where('label', 'E-Mail-Adresse')->update(['required' => true]);

    followUpSubmitFirstForm($this->form)->assertInertia(fn (Assert $page): Assert => $page->component('Forms/ConfirmationPending'));
    $confirmationToken = basename((string) parse_url(Mail::queued(FormSubmissionConfirmation::class)->sole()->confirmationUrl, PHP_URL_PATH));

    $response = $this->post(route('form.confirm', $confirmationToken));
    $token = followUpTokenFromNotice($response);

    $this->get(route('map-point.manage.follow-up', $token))->assertInertia(fn (Assert $page): Assert => $page->component('MapPointManage/FollowUp'));
    expect(basename((string) parse_url(Mail::queued(MapPointCreated::class)->sole()->manageUrl, PHP_URL_PATH)))->toBe($token);
});

test('without a contact address the button still leads to the follow-up form', function (): void {
    $response = followUpSubmitFirstForm($this->form, email: null);

    Mail::assertNotQueued(MapPointCreated::class);
    $this->get(route('map-point.manage.follow-up', followUpTokenFromNotice($response)))
        ->assertInertia(fn (Assert $page): Assert => $page->component('MapPointManage/FollowUp'));
});

test('without a valid token there is no access, an expired one asks for a new link', function (): void {
    $token = followUpTokenFromNotice(followUpSubmitFirstForm($this->form));

    $this->get(route('map-point.manage.follow-up', 'falsch'))->assertInertia(fn (Assert $page): Assert => $page->component('MapPointManage/Message')->where('status', 'invalid'));
    followUpSubmit($this->followUp, 'falsch', ['Größe' => '300 m²'])->assertInertia(fn (Assert $page): Assert => $page->component('MapPointManage/Message')->where('status', 'invalid'));

    $this->travel(8)->days();
    $this->get(route('map-point.manage.follow-up', $token))->assertInertia(fn (Assert $page): Assert => $page->where('status', 'invalid'));
    followUpSubmit($this->followUp, $token, ['Größe' => '300 m²'])->assertInertia(fn (Assert $page): Assert => $page->where('status', 'invalid'));
    expect(MapPoint::sole()->fields()->count())->toBe(0);
});

test('a point whose form has no follow-up form offers none', function (): void {
    $this->form->mapPointCreator->followUpFormDefinition()->dissociate()->save();
    followUpSubmitFirstForm($this->form)->assertInertia(fn (Assert $page): Assert => $page->where('notices.0.url', null));
    $token = app(MapPointEditLinkService::class)->issue(MapPoint::sole());

    $this->get(route('map-point.manage.show', $token))->assertInertia(fn (Assert $page): Assert => $page->where('mapPoint.follow_up_button_text', null));
    $this->get(route('map-point.manage.follow-up', $token))->assertNotFound();
});

test('only mapped fields are written and an empty field keeps its value', function (): void {
    $token = followUpTokenFromNotice(followUpSubmitFirstForm($this->form, ['Größe' => '200 m²']));

    followUpSubmit($this->followUp, $token, ['Größe' => '', 'Art' => 'teich', 'Igeltor: Breite' => '13'])
        ->assertSessionHasNoErrors()
        ->assertInertia(fn (Assert $page): Assert => $page->component('MapPointManage/FollowUpSubmitted'));

    $mapPoint = MapPoint::sole();
    expect($mapPoint->fields()->pluck('value', 'label')->all())->toBe(['Größe' => '200 m²', 'Art' => 'teich'])
        ->and($mapPoint->title)->toBe('Naturgarten Müller')
        ->and(FormSubmission::where('map_point_id', $mapPoint->id)->count())->toBe(1);
});

test('characteristics are added and removed, the values of a removed one become former values', function (): void {
    $token = followUpTokenFromNotice(followUpSubmitFirstForm($this->form));
    $gate = $this->hedgehogGate->uuid;

    followUpSubmit($this->followUp, $token, ['Maßnahmen' => [$gate], 'Igeltor: Breite' => '13'])->assertSessionHasNoErrors();
    $mapPoint = MapPoint::sole();
    expect($mapPoint->characteristics()->pluck('name')->all())->toBe(['Igeltor'])
        ->and($mapPoint->fields()->pluck('value', 'label')->all())->toBe(['Breite' => 13]);

    followUpSubmit($this->followUp, $token, ['Größe' => '250 m²'])->assertSessionHasNoErrors();
    expect($mapPoint->characteristics()->count())->toBe(0)
        ->and($mapPoint->fields()->where('form_field_id', $this->width->id)->exists())->toBeTrue();
});

test('a new image is added, a removed one is deleted with its file, the limit counts both', function (): void {
    $token = followUpTokenFromNotice(followUpSubmitFirstForm($this->form));
    $photo = followUpFieldUuid($this->followUp, 'Foto');

    followUpSubmit($this->followUp, $token, ['Foto' => [UploadedFile::fake()->image('teich.jpg')]])->assertSessionHasNoErrors();
    $mapPoint = MapPoint::sole();
    [$first] = followUpImagePaths($mapPoint, $this->photo);
    Storage::disk('images')->assertExists($first);

    followUpSubmit($this->followUp, $token, ['Foto' => [UploadedFile::fake()->image('beet.jpg')]])->assertSessionHasNoErrors();
    expect(followUpImagePaths($mapPoint, $this->photo))->toHaveCount(2)->toContain($first);

    followUpSubmit($this->followUp, $token, ['Foto' => [UploadedFile::fake()->image('drittes.jpg')]])->assertSessionHasErrors($photo);
    expect(followUpImagePaths($mapPoint, $this->photo))->toHaveCount(2);

    followUpSubmit($this->followUp, $token, ['Foto' => [UploadedFile::fake()->image('drittes.jpg')]], ['_removed_images' => [$photo => [basename($first)]]])->assertSessionHasNoErrors();
    $paths = followUpImagePaths($mapPoint, $this->photo);
    expect($paths)->toHaveCount(2)->not->toContain($first);
    Storage::disk('images')->assertMissing($first);

    followUpSubmit($this->followUp, $token, [], ['_removed_images' => [$photo => array_map(basename(...), $paths)]])->assertSessionHasNoErrors();
    expect(followUpImagePaths($mapPoint, $this->photo))->toBe([]);
});

test('the form shows the current values with their options and images, the images only with the token', function (): void {
    $token = followUpTokenFromNotice(followUpSubmitFirstForm($this->form, ['Größe' => '200 m²', 'Art' => 'teich', 'Maßnahmen' => [$this->hedgehogGate->uuid], 'Igeltor: Breite' => '13']));
    followUpSubmit($this->followUp, $token, ['Foto' => [UploadedFile::fake()->image('teich.jpg')]]);
    $mapPoint = MapPoint::sole();
    $name = basename(followUpImagePaths($mapPoint, $this->photo)[0]);
    $artOption = $this->followUp->fields()->where('label', 'Art')->sole()->options()->where('label', 'Teich')->sole()->value;

    $response = $this->get(route('map-point.manage.follow-up', $token));
    $response->assertInertia(fn (Assert $page): Assert => $page
        ->where('initialData.'.followUpFieldUuid($this->followUp, 'Größe'), '200 m²')
        ->where('initialData.'.followUpFieldUuid($this->followUp, 'Art'), $artOption)
        ->where('initialData.'.followUpFieldUuid($this->followUp, 'Maßnahmen'), [$this->hedgehogGate->uuid])
        ->where('initialData.'.followUpFieldUuid($this->followUp, 'Igeltor: Breite'), 13)
        ->where('initialData.'.followUpFieldUuid($this->followUp, 'Foto'), [$name])
        ->where('storedImages.'.followUpFieldUuid($this->followUp, 'Foto').'.0.url', route('map-point.manage.image', [$token, $name])));

    expect($mapPoint->published)->toBeFalse();
    $this->get(route('map-point.manage.image', [$token, $name]))->assertOk();
    $this->get(route('map-point.manage.image', ['falsch', $name]))->assertNotFound();
    $this->get(route('map-point.manage.image', [$token, 'fremd.jpg']))->assertNotFound();
    $this->get(route('map-point-images.show', [$mapPoint, $name]))->assertNotFound();
});

test('a new value in a consented field is public at once, fields of a new characteristic only after showing them too', function (): void {
    $token = followUpTokenFromNotice(followUpSubmitFirstForm($this->form));
    $mapPoint = MapPoint::sole();
    $mapPoint->update(['published' => true]);
    $embed = MapEmbed::factory()->for($this->group)->create();
    $embed->mapPointCategories()->sync([$this->category->id]);
    $publicLabels = fn (): array => collect($this->get(route('map.public', $embed))->viewData('page')['props']['pointsByCategory'][$this->category->uuid][0]['fields'])->pluck('label')->all();

    followUpSubmit($this->followUp, $token, ['Größe' => '300 m²', 'Maßnahmen' => [$this->hedgehogGate->uuid], 'Igeltor: Breite' => '13'])
        ->assertInertia(fn (Assert $page): Assert => $page
            ->component('MapPointManage/FollowUpSubmitted')
            ->where('newDetails.labels', ['Breite']));

    expect($publicLabels())->toBe(['Größe']);

    $this->put(route('map-point.manage.consent', $token), ['show' => true, 'details' => true])->assertRedirect(route('map-point.manage.show', $token));

    expect($mapPoint->fresh()->public_consent_fields)->toContain('field:'.$this->width->uuid)
        ->and($mapPoint->fresh()->public_consent_text)->toContain('Breite')
        ->and($publicLabels())->toBe(['Größe', 'Breite']);
});

test('with a consent to the point alone the follow-up form asks for nothing more', function (): void {
    $token = followUpTokenFromNotice(followUpSubmitFirstForm($this->form, details: false));

    followUpSubmit($this->followUp, $token, ['Maßnahmen' => [$this->hedgehogGate->uuid], 'Igeltor: Breite' => '13'])
        ->assertInertia(fn (Assert $page): Assert => $page->where('newDetails', null));
    expect(MapPoint::sole()->public_consent_fields)->toBe(['coordinate']);
});

test('the follow-up form does not change the consent, title or location', function (): void {
    $token = followUpTokenFromNotice(followUpSubmitFirstForm($this->form));
    $before = MapPoint::sole()->only(['title', 'description', 'location', 'category_id', 'public_consent', 'public_consent_fields', 'public_consent_text', 'contact_email']);

    followUpSubmit($this->followUp, $token, ['Größe' => '300 m²', 'Maßnahmen' => [$this->hedgehogGate->uuid]]);

    expect(MapPoint::sole()->only(array_keys($before)))->toBe($before)
        ->and(MapPoint::sole()->public_consent)->toBe(PublicConsent::GIVEN);
});

test('the follow-up form is rate limited per point', function (): void {
    Config::set('forms.map_point_follow_up.max_per_point_per_hour', 2);
    $token = followUpTokenFromNotice(followUpSubmitFirstForm($this->form));

    followUpSubmit($this->followUp, $token, ['Größe' => '1'])->assertOk();
    followUpSubmit($this->followUp, $token, ['Größe' => '2'])->assertOk();
    followUpSubmit($this->followUp, $token, ['Größe' => '3'])->assertTooManyRequests();
    expect(MapPoint::sole()->fields()->pluck('value')->all())->toBe(['2']);
});

test('the token of the page after submitting does not confirm the address, the link of the mail does', function (): void {
    $token = followUpTokenFromNotice(followUpSubmitFirstForm($this->form));
    $mapPoint = MapPoint::sole();

    $this->get(route('map-point.manage.show', $token))->assertInertia(fn (Assert $page): Assert => $page->where('proof', null));
    $this->put(route('map-point.manage.contact', $token), ['enabled' => true])->assertRedirect(route('map-point.manage.show', $token));
    expect($mapPoint->fresh()->contact_email_verified_at)->toBeNull()
        ->and($mapPoint->fresh()->hasContact())->toBeFalse();

    $mailUrl = Mail::queued(MapPointCreated::class)->sole()->manageUrl;
    parse_str((string) parse_url($mailUrl, PHP_URL_QUERY), $query);
    $proof = $query['nachweis'];
    $this->get($mailUrl)->assertInertia(fn (Assert $page): Assert => $page->where('proof', $proof));
    $this->put(route('map-point.manage.contact', ['token' => $token, 'nachweis' => 'gefälscht']), ['enabled' => true]);
    expect($mapPoint->fresh()->contact_email_verified_at)->toBeNull();

    $this->put(route('map-point.manage.contact', ['token' => $token, 'nachweis' => $proof]), ['enabled' => true])
        ->assertRedirect(route('map-point.manage.show', ['token' => $token, 'nachweis' => $proof]));
    expect($mapPoint->fresh()->contact_email_verified_at)->not->toBeNull()
        ->and($mapPoint->fresh()->hasContact())->toBeTrue();
});

test('deleting the point on the manage page deletes the follow-up submissions with their images, except one with an advice', function (): void {
    $token = followUpTokenFromNotice(followUpSubmitFirstForm($this->form));
    followUpSubmit($this->followUp, $token, ['Foto' => [UploadedFile::fake()->image('teich.jpg')]]);
    followUpSubmit($this->followUp, $token, ['Größe' => '300 m²']);
    [$withImage, $withAdvice] = FormSubmission::whereNotNull('map_point_id')->orderBy('id')->get()->all();
    $withAdvice->update(['advice_id' => Advice::factory()->create()->id]);
    $imageDirectory = $withImage->imageDirectory();
    Storage::disk('images')->assertDirectoryExists($imageDirectory);

    $this->delete(route('map-point.manage.destroy', $token))->assertInertia(fn (Assert $page): Assert => $page->where('status', 'deleted'));

    expect(MapPoint::count())->toBe(0)
        ->and(FormSubmission::find($withImage->id))->toBeNull()
        ->and($withAdvice->fresh()->map_point_id)->toBeNull()
        ->and($withAdvice->fresh()->map_point_deleted_by?->value)->toBe('person');
    Storage::disk('images')->assertDirectoryMissing($imageDirectory);
});

test('admins see the follow-up submissions at the point and keep them when they delete it', function (): void {
    $token = followUpTokenFromNotice(followUpSubmitFirstForm($this->form));
    followUpSubmit($this->followUp, $token, ['Größe' => '300 m²']);
    $mapPoint = MapPoint::sole();
    $admin = followUpAdmin($this->group);

    $this->actingAs($admin)->get(route('mappoints.edit', $mapPoint))
        ->assertInertia(fn (Assert $page): Assert => $page->has('consent.follow_up_submissions', 1)
            ->where('consent.follow_up_submissions.0.form_name', 'Erzähl mehr: Garten'));

    $this->actingAs($admin)->delete(route('mappoints.destroy', $mapPoint));

    $submission = FormSubmission::whereNotNull('map_point_deleted_at')->where('form_definition_id', $this->followUp->id)->sole();
    expect($submission->map_point_id)->toBeNull()
        ->and($submission->map_point_deleted_by?->value)->toBe('admin');
});

test('the form builder validates the target per mode', function (): void {
    $admin = followUpAdmin($this->group);
    $payload = fn (FormDefinition $form): array => FormDefinitionData::fromModel($form->load('fields.options'))->toArray();

    $followUp = $payload($this->followUp);
    $followUp['requires_email_confirmation'] = true;
    $this->actingAs($admin)->put(route('form-definitions.update', $this->followUp), $followUp)->assertSessionHasErrors('requires_email_confirmation');

    $followUp['requires_email_confirmation'] = false;
    $this->actingAs($admin)->put(route('form-definitions.update', $this->followUp), $followUp)->assertSessionHasNoErrors();
    expect($this->followUp->mapPointCreator->fresh()->mode)->toBe(MapPointTargetMode::UPDATE);

    $create = $payload($this->form);
    $create['map_point_mapping']['title_field_id'] = null;
    $this->actingAs($admin)->put(route('form-definitions.update', $this->form), $create)->assertSessionHasErrors('map_point_mapping.title_field_id');
});

test('the follow-up form must belong to the initiative, add to points and fit their category', function (): void {
    $admin = followUpAdmin($this->group);
    $otherCategory = MapPointCategory::factory()->for($this->group)->create(['name' => 'Balkon']);
    $otherCategory->findOrCreateFormDefinition()->fields()->create(['type' => FieldType::TEXT, 'label' => 'Ausrichtung', 'required' => false, 'sort_order' => 0]);
    $otherFollowUp = app(FormDefinitionService::class)->createFromTemplate('map_point', $this->group->uuid, $otherCategory, withFollowUp: true)->mapPointCreator->followUpFormDefinition;
    $foreignFollowUp = app(FormDefinitionService::class)->createFromTemplate('map_point', Group::factory()->create()->uuid, $this->category, withFollowUp: true)->mapPointCreator->followUpFormDefinition;
    $plainForm = app(FormDefinitionService::class)->createFromTemplate('map_point', $this->group->uuid, $this->category);

    /** @return TestResponse<Response> */
    $link = function (FormDefinition $followUp) use ($admin): TestResponse {
        $data = FormDefinitionData::fromModel($this->form->load('fields.options'))->toArray();
        $data['map_point_mapping']['follow_up_form_definition_id'] = $followUp->uuid;
        $data['map_point_mapping']['follow_up_button_text'] = 'Mehr erzählen';

        return $this->actingAs($admin)->put(route('form-definitions.update', $this->form), $data);
    };

    $link($plainForm)->assertSessionHasErrors(['map_point_mapping.follow_up_form_definition_id' => 'Als Folgeformular eignet sich nur ein Formular, das bestehende Kartenpunkte ergänzt.']);
    $link($foreignFollowUp)->assertSessionHasErrors(['map_point_mapping.follow_up_form_definition_id' => 'Das Folgeformular muss zur selben Initiative gehören.']);
    $link($otherFollowUp)->assertSessionHasErrors(['map_point_mapping.follow_up_form_definition_id' => 'Das Folgeformular passt nicht zur Kategorie: Es muss im selben Zweig liegen und dieselben Felder haben.']);
    $link($this->followUp)->assertSessionHasNoErrors();
    expect($this->form->mapPointCreator->fresh()->follow_up_button_text)->toBe('Mehr erzählen');

    $this->actingAs($admin)->get(route('form-definitions.edit', $this->form))
        ->assertInertia(fn (Assert $page): Assert => $page->where('mapPointFollowUpForms', fn ($forms): bool => collect($forms)->firstWhere('id', $this->followUp->uuid)['category_ids'] === [$this->category->uuid]));
});

test('the button for the next form stays as it is next to the follow-up button', function (): void {
    $this->form->update(['show_next_form_button' => true, 'next_form_button_text' => 'Noch einen Garten eintragen']);

    followUpSubmitFirstForm($this->form)->assertInertia(fn (Assert $page): Assert => $page
        ->where('formDefinition.show_next_form_button', true)
        ->where('formDefinition.next_form_button_text', 'Noch einen Garten eintragen')
        ->where('notices.0.url_label', 'Erzähl mehr'));
});

test('the wizard needs a category for a follow-up form', function (): void {
    $admin = followUpAdmin($this->group);

    $this->actingAs($admin)->post(route('form-definitions.from-template'), [
        'template_type' => 'map_point',
        'group_id' => $this->group->uuid,
        'with_follow_up' => true,
    ])->assertSessionHasErrors('with_follow_up');

    $this->actingAs($admin)->post(route('form-definitions.from-template'), [
        'template_type' => 'map_point',
        'group_id' => $this->group->uuid,
        'map_point_category_id' => $this->category->uuid,
        'with_follow_up' => true,
        'follow_up_only_field_ids' => [$this->size->uuid],
    ])->assertSessionHasNoErrors();

    $created = FormDefinition::latest('id')->whereHas('mapPointCreator', fn ($query) => $query->where('mode', 'create'))->first();
    expect($created->fields()->pluck('label')->all())->not->toContain('Größe')->toContain('Foto')
        ->and($created->mapPointCreator->followUpFormDefinition->fields()->pluck('label')->all())->toContain('Größe');
});
