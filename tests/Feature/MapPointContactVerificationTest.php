<?php

use App\Data\FormDefinitionData;
use App\Enums\FieldType;
use App\Mail\MapPointContact\ContactEmailVerification;
use App\Mail\MapPointContact\ContactRequestConfirmation;
use App\Models\FormDefinitionToMapPoint;
use App\Models\FormField;
use App\Models\FormFieldOption;
use App\Models\Group;
use App\Models\MapEmbed;
use App\Models\MapPoint;
use App\Models\MapPointCategory;
use App\Models\MapPointContactRequest;
use App\Models\User;
use App\Services\MapPointContactService;
use App\Services\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\MailManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\AutoAttachesFormEmbedToken;
use Wnx\Sends\Models\Send;

uses(RefreshDatabase::class, AutoAttachesFormEmbedToken::class);

beforeEach(function (): void {
    Mail::fake();
});

function contactMappedForm(): FormDefinitionToMapPoint
{
    $config = FormDefinitionToMapPoint::factory()->create();

    $emailField = FormField::factory()->create(['type' => FieldType::EMAIL, 'label' => 'E-Mail', 'required' => false]);
    $enabledField = FormField::factory()->create(['type' => FieldType::CHECKBOX, 'label' => 'Kontakt', 'required' => false]);
    $enabledField->options()->delete();
    FormFieldOption::create(['form_field_id' => $enabledField->id, 'label' => 'Ja, Kontakt erlauben', 'value' => 'yes', 'sort_order' => 0, 'is_default' => false, 'is_required' => false]);
    $config->formDefinition->fields()->saveMany([$emailField, $enabledField]);

    $config->contactEmailField()->associate($emailField);
    $config->contactEnabledField()->associate($enabledField);
    $config->save();

    return $config->fresh();
}

/**
 * @param  array<int, string>  $enabled
 * @return array<string, mixed>
 */
function contactMappedSubmission(FormDefinitionToMapPoint $config, ?string $email, array $enabled): array
{
    return [
        $config->titleField->uuid => 'Mein Garten',
        $config->descriptionField->uuid => 'Mit Balkonkraftwerk',
        $config->coordinateField->uuid => ['lat' => 49.8728, 'lng' => 8.6510],
        $config->contactEmailField->uuid => $email,
        $config->contactEnabledField->uuid => $enabled,
    ];
}

function verificationToken(): string
{
    $mail = Mail::queued(ContactEmailVerification::class)->last();

    return basename((string) parse_url($mail->verificationUrl, PHP_URL_PATH));
}

test('a point from a form takes over the contact fields and sends an activation mail', function (): void {
    $config = contactMappedForm();

    $this->post(route('form.submit', $config->formDefinition), contactMappedSubmission($config, 'owner@example.org', ['yes']))
        ->assertSessionHasNoErrors();

    $point = MapPoint::sole();
    expect($point->contact_email)->toBe('owner@example.org')
        ->and($point->contact_enabled)->toBeTrue()
        ->and($point->contact_email_verified_at)->toBeNull();

    Mail::assertQueued(ContactEmailVerification::class, fn ($mail) => $mail->hasTo('owner@example.org'));
    Mail::queued(ContactEmailVerification::class)->sole()->assertSeeInHtml($config->formDefinition->group->name);
});

test('an unchecked checkbox leaves contact disabled', function (): void {
    $config = contactMappedForm();

    $this->post(route('form.submit', $config->formDefinition), contactMappedSubmission($config, 'owner@example.org', []));

    expect(MapPoint::sole()->contact_enabled)->toBeFalse();
});

test('without an address no activation mail is sent', function (): void {
    $config = contactMappedForm();

    $this->post(route('form.submit', $config->formDefinition), contactMappedSubmission($config, null, ['yes']));

    expect(MapPoint::sole()->contact_email)->toBeNull();
    Mail::assertNotQueued(ContactEmailVerification::class);
});

test('a form point can only be contacted after the activation link was used', function (): void {
    $config = contactMappedForm();
    $this->post(route('form.submit', $config->formDefinition), contactMappedSubmission($config, 'owner@example.org', ['yes']));

    $point = MapPoint::sole();
    $category = MapPointCategory::factory()->for($point->group)->create();
    $point->update(['published' => true, 'category_id' => $category->id]);
    $embed = MapEmbed::factory()->for($point->group)->create();
    $embed->mapPointCategories()->sync([$category->id]);

    $this->get(route('map.public', $embed))
        ->assertInertia(fn ($page) => $page->where("pointsByCategory.{$category->uuid}.0.has_contact", false));
    $this->post(route('map-contact.store', [$embed, $point]), [
        'name' => 'Erika', 'email' => 'erika@example.org', 'message' => 'Hallo',
    ])->assertNotFound();

    $token = verificationToken();

    $this->get(route('map-contact.verify.show', $token))
        ->assertInertia(fn ($page) => $page->component('MapPointContact/Verify')->where('status', 'pending'));
    expect($point->fresh()->contact_email_verified_at)->toBeNull();

    $this->post(route('map-contact.verify', $token))
        ->assertInertia(fn ($page) => $page->where('status', 'verified'));
    expect($point->fresh()->contact_email_verified_at)->not->toBeNull();

    $this->post(route('map-contact.verify', $token))
        ->assertInertia(fn ($page) => $page->where('status', 'invalid'));

    $this->get(route('map.public', $embed))
        ->assertInertia(fn ($page) => $page->where("pointsByCategory.{$category->uuid}.0.has_contact", true));
    $this->post(route('map-contact.store', [$embed, $point]), [
        'name' => 'Erika', 'email' => 'erika@example.org', 'message' => 'Hallo',
    ])->assertOk();
    Mail::assertQueued(ContactRequestConfirmation::class);
});

test('an expired activation link is rejected', function (): void {
    $config = contactMappedForm();
    $this->post(route('form.submit', $config->formDefinition), contactMappedSubmission($config, 'owner@example.org', ['yes']));
    $token = verificationToken();

    $this->travel(8)->days();

    $this->post(route('map-contact.verify', $token))
        ->assertInertia(fn ($page) => $page->where('status', 'expired'));
    expect(MapPoint::sole()->contact_email_verified_at)->toBeNull();
});

test('the form target accepts only an email field and a checkbox with exactly one option', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $config = contactMappedForm();
    $form = $config->formDefinition->load('fields.options');

    $payload = function (string $emailFieldId, string $enabledFieldId) use ($form): array {
        $data = FormDefinitionData::fromModel($form)->toArray();
        $data['map_point_mapping']['contact_email_field_id'] = $emailFieldId;
        $data['map_point_mapping']['contact_enabled_field_id'] = $enabledFieldId;

        return $data;
    };

    app(SessionService::class)->actAsGroup($form->group);
    Config::set('app.group_context', 'global');

    $this->actingAs($admin)
        ->put(route('form-definitions.update', $form), $payload($config->titleField->uuid, $config->contactEmailField->uuid))
        ->assertSessionHasErrors(['map_point_mapping.contact_email_field_id', 'map_point_mapping.contact_enabled_field_id']);

    $this->actingAs($admin)
        ->put(route('form-definitions.update', $form), $payload($config->contactEmailField->uuid, $config->contactEnabledField->uuid))
        ->assertSessionHasNoErrors();

    expect($form->mapPointCreator->fresh()->contact_email_field_id)->toBe($config->contactEmailField->id);
});

test('an address entered by an admin counts as confirmed', function (): void {
    $group = Group::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    app(SessionService::class)->actAsGroup($group);
    Config::set('app.group_context', 'global');
    $point = MapPoint::factory()->for($group)->create();

    $this->actingAs($admin)->put(route('mappoints.update', $point), [
        'title' => $point->title,
        'coordinate' => ['lat' => 49.87, 'lng' => 8.65],
        'published' => true,
        'group_id' => $group->uuid,
        'contact_email' => 'admin-set@example.org',
        'contact_enabled' => true,
    ])->assertSessionHasNoErrors();

    $point->refresh();
    expect($point->contact_email)->toBe('admin-set@example.org');
    expect($point->contact_email_verified_at)->not->toBeNull();
    expect($point->hasContact())->toBeTrue();
    Mail::assertNotQueued(ContactEmailVerification::class);

    $this->actingAs($admin)->post(route('mappoints.contact-verification', $point))->assertRedirect();

    expect($point->fresh()->contact_email_verified_at)->toBeNull();
    Mail::assertQueued(ContactEmailVerification::class, fn ($mail) => $mail->hasTo('admin-set@example.org'));
});

test('the edit page lists the contact requests of the point', function (): void {
    $group = Group::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    app(SessionService::class)->actAsGroup($group);
    Config::set('app.group_context', 'global');
    $point = MapPoint::factory()->for($group)->create();
    MapPointContactRequest::factory()->for($point)->confirmed()->create(['sender_name' => 'Erika']);
    MapPointContactRequest::factory()->for($point)->create();

    $this->actingAs($admin)->get(route('mappoints.edit', $point))
        ->assertInertia(fn ($page) => $page
            ->has('contactRequests', 2)
            ->where('contactRequests', fn (Collection $requests): bool => $requests->pluck('status')->sort()->values()->all() === ['forwarded', 'pending'])
        );
});

test('mails with a one-time link are stored without content', function (): void {
    Mail::swap(new MailManager(app()));
    config(['mail.default' => 'array', 'queue.default' => 'sync']);

    $point = MapPoint::factory()->create(['contact_email' => 'owner@example.org']);
    app(MapPointContactService::class)->requestVerification($point);

    $send = Send::sole();
    expect($send->mail_class)->toBe(ContactEmailVerification::class)
        ->and($send->content)->toBeNull();
});
