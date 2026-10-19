<?php

use App\Enums\FieldType;
use App\Enums\PublicConsent;
use App\Events\MapPointCreatedByFormSubmission;
use App\Mail\FormSubmissionConfirmation;
use App\Mail\MapPointCreated;
use App\Mail\MapPointEditLinks;
use App\Models\FormDefinitionToMapPoint;
use App\Models\FormSubmission;
use App\Models\MapPoint;
use App\Models\MapPointCategory;
use App\Models\SubmissionField;
use App\Models\User;
use App\Services\SessionService;
use App\Support\TokenHash;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
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
    $this->category = MapPointCategory::factory()->for($this->form->group)->create(['name' => 'Garten']);
    $this->photo = $this->category->findOrCreateFormDefinition()->fields()->create(['type' => FieldType::IMAGE, 'label' => 'Foto', 'required' => false, 'sort_order' => 0, 'max_images' => 1]);
    $this->formPhoto = $this->form->fields()->create(['type' => FieldType::IMAGE, 'label' => 'Foto', 'required' => false, 'sort_order' => 10, 'max_images' => 1]);
    $this->email = $this->form->fields()->create(['type' => FieldType::EMAIL, 'label' => 'E-Mail', 'required' => true, 'sort_order' => 11]);
    $this->config->category()->associate($this->category);
    $this->config->contactEmailField()->associate($this->email);
    $this->config->save();
    $this->config->fieldMappings()->create(['target_field_id' => $this->photo->id, 'source_field_id' => $this->formPhoto->id]);
});

/**
 * @param  array<string, mixed>  $values
 * @return TestResponse<Response>
 */
function submitEditLinkForm(array $values = []): TestResponse
{
    $test = test();

    return $test->post(route('form.submit', $test->form), [
        $test->config->titleField->uuid => 'Naturgarten Müller',
        $test->config->descriptionField->uuid => 'Mit Teich',
        $test->config->coordinateField->uuid => ['lat' => 49.8728, 'lng' => 8.6510],
        $test->formPhoto->uuid => [UploadedFile::fake()->image('teich.jpg')],
        $test->email->uuid => 'erika@example.com',
        '_consent_show' => true,
        ...$values,
    ]);
}

function tokenOfUrl(string $url): string
{
    return basename(parse_url($url, PHP_URL_PATH));
}

function createdMailToken(): string
{
    return tokenOfUrl(Mail::queued(MapPointCreated::class)->last()->manageUrl);
}

function editLinksMailToken(): string
{
    return tokenOfUrl(Mail::queued(MapPointEditLinks::class)->last()->links[0]['url']);
}

/**
 * @return TestResponse<Response>
 */
function openManagePage(string $token): TestResponse
{
    return test()->get(route('map-point.manage.show', $token));
}

function assertManagePageIsInvalid(string $token): void
{
    openManagePage($token)->assertInertia(fn (Assert $page): Assert => $page->component('MapPointManage/Message')->where('status', 'invalid'));
}

test('a form without confirmation sends the mail with the manage link once and tells the submitter', function (): void {
    submitEditLinkForm()
        ->assertInertia(fn (Assert $page): Assert => $page
            ->component('Forms/Submitted')
            ->where('notices.0.title', 'Dein Eintrag ist angelegt.')
        );

    Mail::assertQueued(MapPointCreated::class, 1);
    Mail::assertQueued(MapPointCreated::class, fn (MapPointCreated $mail): bool => $mail->hasTo('erika@example.com'));
    openManagePage(createdMailToken())->assertInertia(fn (Assert $page): Assert => $page
        ->component('MapPointManage/Manage')
        ->where('mapPoint.title', 'Naturgarten Müller')
        ->where('mapPoint.public_consent', 'given')
    );
});

test('a form with confirmation sends the mail only after the confirmation', function (): void {
    $this->form->update(['requires_email_confirmation' => true]);
    submitEditLinkForm();
    Mail::assertNotQueued(MapPointCreated::class);

    $confirmationToken = tokenOfUrl(Mail::queued(FormSubmissionConfirmation::class)->last()->confirmationUrl);
    $this->post(route('form.confirm', $confirmationToken))
        ->assertInertia(fn (Assert $page): Assert => $page
            ->component('Forms/Submitted')
            ->where('notices.0.title', 'Dein Eintrag ist angelegt.')
        );
    $this->post(route('form.confirm', $confirmationToken));

    Mail::assertQueued(MapPointCreated::class, 1);
    assertManagePageIsInvalid($confirmationToken);
    expect(MapPoint::sole()->edit_token_hash)->not->toBe(FormSubmission::sole()->confirmation_token_hash);
});

test('no mail leaves when the point is rolled back', function (): void {
    Event::listen(MapPointCreatedByFormSubmission::class, fn () => throw new RuntimeException('später fehlgeschlagen'));

    submitEditLinkForm();

    expect(MapPoint::count())->toBe(0);
    Mail::assertNotQueued(MapPointCreated::class);
});

test('only a keyed hash of the token is stored and the link expires', function (): void {
    submitEditLinkForm();
    $token = createdMailToken();

    expect(MapPoint::sole()->edit_token_hash)->toBe(TokenHash::make($token));

    $this->travel(8)->days();
    assertManagePageIsInvalid($token);
});

test('a new link replaces the old one', function (): void {
    submitEditLinkForm();
    $oldToken = createdMailToken();

    $this->post(route('map-point.manage.send-for-point', MapPoint::sole()))
        ->assertInertia(fn (Assert $page): Assert => $page->component('MapPointManage/Message')->where('status', 'sent'));

    assertManagePageIsInvalid($oldToken);
    openManagePage(editLinksMailToken())->assertInertia(fn (Assert $page): Assert => $page->component('MapPointManage/Manage'));
});

test('requesting links by address answers the same for unknown addresses', function (): void {
    submitEditLinkForm();

    $this->post(route('map-point.manage.send-for-address'), ['email' => 'unbekannt@example.com'])
        ->assertInertia(fn (Assert $page): Assert => $page->component('MapPointManage/Message')->where('status', 'sent'));
    Mail::assertNotQueued(MapPointEditLinks::class);

    $this->post(route('map-point.manage.send-for-address'), ['email' => 'Erika@Example.com'])
        ->assertInertia(fn (Assert $page): Assert => $page->component('MapPointManage/Message')->where('status', 'sent'));
    Mail::assertQueued(MapPointEditLinks::class, fn (MapPointEditLinks $mail): bool => count($mail->links) === 1 && $mail->links[0]['title'] === 'Naturgarten Müller');
});

test('requesting a link for a point without contact address answers the same and sends nothing', function (): void {
    $mapPoint = MapPoint::factory()->create();

    $this->post(route('map-point.manage.send-for-point', $mapPoint))
        ->assertInertia(fn (Assert $page): Assert => $page->component('MapPointManage/Message')->where('status', 'sent'));

    Mail::assertNotQueued(MapPointEditLinks::class);
});

test('requests are rate limited per address, per point and per ip', function (string $limit): void {
    submitEditLinkForm();
    $mapPoint = MapPoint::sole();
    Config::set('forms.map_point_edit.max_per_address_per_hour', 100);
    Config::set('forms.map_point_edit.max_per_point_per_hour', 100);
    Config::set('forms.map_point_edit.max_per_ip_per_hour', 100);
    Config::set("forms.map_point_edit.max_per_{$limit}_per_hour", 2);

    $request = fn (int $attempt): TestResponse => match ($limit) {
        'address' => $this->withServerVariables(['REMOTE_ADDR' => "10.0.0.{$attempt}"])->post(route('map-point.manage.send-for-address'), ['email' => 'erika@example.com']),
        'point' => $this->withServerVariables(['REMOTE_ADDR' => "10.0.0.{$attempt}"])->post(route('map-point.manage.send-for-point', $mapPoint)),
        'ip' => $this->post(route('map-point.manage.send-for-address'), ['email' => "person{$attempt}@example.com"]),
    };

    $request(1)->assertOk();
    $request(2)->assertOk();
    $request(3)->assertStatus(429);
})->with(['address', 'point', 'ip']);

test('the person can change the consent levels', function (): void {
    submitEditLinkForm();
    $token = createdMailToken();
    $this->travel(1)->hours();

    $this->put(route('map-point.manage.consent', $token), ['show' => true, 'details' => true])
        ->assertRedirect(route('map-point.manage.show', $token));

    $mapPoint = MapPoint::sole();
    expect($mapPoint->public_consent)->toBe(PublicConsent::GIVEN)
        ->and($mapPoint->public_consent_fields)->toBe(['coordinate', 'title', 'description'])
        ->and($mapPoint->public_consent_at->isSameMinute(now()))->toBeTrue()
        ->and($mapPoint->public_consent_text)->toContain('Titel, Beschreibung');

    $this->put(route('map-point.manage.consent', $token), ['show' => false, 'details' => true]);
    expect(MapPoint::sole()->public_consent)->toBe(PublicConsent::REFUSED);
});

test('the person can withdraw the consent', function (): void {
    submitEditLinkForm();
    $token = createdMailToken();

    $this->post(route('map-point.manage.revoke', $token))->assertRedirect(route('map-point.manage.show', $token));

    expect(MapPoint::sole()->public_consent)->toBe(PublicConsent::REFUSED);
});

test('deleting removes the point, its images and the submission', function (): void {
    submitEditLinkForm();
    $mapPoint = MapPoint::sole();
    $submission = FormSubmission::sole();
    $pointImages = Storage::disk('images')->allFiles($mapPoint->imageDirectory());
    $submissionImages = Storage::disk('images')->allFiles($submission->imageDirectory());
    expect($pointImages)->toHaveCount(1)->and($submissionImages)->toHaveCount(1);

    $this->delete(route('map-point.manage.destroy', createdMailToken()))
        ->assertInertia(fn (Assert $page): Assert => $page->component('MapPointManage/Message')->where('status', 'deleted'));

    expect(MapPoint::count())->toBe(0)
        ->and(FormSubmission::count())->toBe(0)
        ->and(SubmissionField::count())->toBe(0)
        ->and(Storage::disk('images')->allFiles())->toBe([]);
});

test('an admin can send the manage link to the contact address', function (): void {
    submitEditLinkForm();
    $mapPoint = MapPoint::sole();
    $admin = User::factory()->create();
    $this->form->group->users()->attach($admin, ['is_admin' => true]);
    app(SessionService::class)->actAsGroup($this->form->group, true);

    $this->actingAs($admin)->post(route('mappoints.send-edit-link', $mapPoint))->assertRedirect();

    Mail::assertQueued(MapPointEditLinks::class, fn (MapPointEditLinks $mail): bool => $mail->hasTo('erika@example.com'));
});
