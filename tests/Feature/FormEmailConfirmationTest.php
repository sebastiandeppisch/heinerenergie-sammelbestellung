<?php

use App\Enums\AdviceType;
use App\Enums\FieldType;
use App\Mail\FormSubmissionConfirmation;
use App\Models\Advice;
use App\Models\FormDefinition;
use App\Models\FormDefinitionToAdvice;
use App\Models\FormField;
use App\Models\FormSubmission;
use App\Models\SubmissionField;
use App\Models\User;
use App\Services\FormSubmissionConfirmationService;
use App\Services\ImageStorage;
use App\Services\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\AutoAttachesFormEmbedToken;

uses(RefreshDatabase::class, AutoAttachesFormEmbedToken::class);

beforeEach(function (): void {
    Mail::fake();
    Http::fake();
});

/**
 * @return array{FormDefinition, FormField}
 */
function confirmedForm(): array
{
    $form = FormDefinition::factory()->create(['requires_email_confirmation' => true, 'is_active' => true]);
    $email = $form->fields()->create(['type' => FieldType::EMAIL, 'label' => 'E-Mail', 'required' => true, 'sort_order' => 0]);

    return [$form, $email];
}

/**
 * @return TestResponse<Response>
 */
function submitConfirmedForm(FormDefinition $form, FormField $email, string $address = 'erika@example.com'): TestResponse
{
    return test()->post(route('form.submit', $form), [$email->uuid => $address]);
}

function issuedToken(): string
{
    $mail = Mail::queued(FormSubmissionConfirmation::class)->last();

    return basename(parse_url($mail->confirmationUrl, PHP_URL_PATH));
}

test('only a keyed hash of the token is stored', function (): void {
    [$form, $email] = confirmedForm();

    submitConfirmedForm($form, $email)->assertSessionHasNoErrors();

    $token = issuedToken();
    $submission = FormSubmission::sole();

    expect($submission->confirmation_token_hash)->not->toBe($token)
        ->and($submission->confirmation_token_hash)->toBe(hash_hmac('sha256', $token, config('app.key')))
        ->and($submission->confirmation_expires_at->isSameDay(now()->addDays(30)))->toBeTrue();
});

test('opening the link does not confirm the submission', function (): void {
    [$form, $email] = confirmedForm();
    submitConfirmedForm($form, $email);

    $this->get(route('form.confirm.show', issuedToken()))
        ->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page->component('Forms/Confirm')->where('status', 'pending'));

    expect(FormSubmission::sole()->confirmed_at)->toBeNull();
});

test('an expired link does not confirm the submission', function (): void {
    [$form, $email] = confirmedForm();
    submitConfirmedForm($form, $email);
    $token = issuedToken();

    $this->travel(31)->days();

    $this->get(route('form.confirm.show', $token))
        ->assertInertia(fn (Assert $page): Assert => $page->component('Forms/Confirm')->where('status', 'expired'));
    $this->post(route('form.confirm', $token))
        ->assertInertia(fn (Assert $page): Assert => $page->component('Forms/Confirm')->where('status', 'expired'));

    expect(FormSubmission::sole()->confirmed_at)->toBeNull();
});

test('an unknown token is rejected', function (): void {
    $this->get(route('form.confirm.show', 'unknown'))
        ->assertInertia(fn (Assert $page): Assert => $page->component('Forms/Confirm')->where('status', 'invalid'));
    $this->post(route('form.confirm', 'unknown'))
        ->assertInertia(fn (Assert $page): Assert => $page->component('Forms/Confirm')->where('status', 'invalid'));
});

function actAsGroupAdmin(FormDefinition $form): void
{
    $user = User::factory()->create();
    $form->group->users()->attach($user, ['is_admin' => true]);
    app(SessionService::class)->actAsGroup($form->group);
    test()->actingAs($user);
}

test('a group admin can resend the confirmation mail with a renewed link', function (): void {
    [$form, $email] = confirmedForm();
    submitConfirmedForm($form, $email);
    $oldToken = issuedToken();
    $submission = FormSubmission::sole();

    $this->travel(31)->days();
    actAsGroupAdmin($form);

    $this->post(route('form-submissions.resend-confirmation', $submission))
        ->assertRedirect()
        ->assertSessionHas('success');

    Mail::assertQueued(FormSubmissionConfirmation::class, 2);
    Mail::assertQueued(FormSubmissionConfirmation::class, fn (FormSubmissionConfirmation $mail): bool => $mail->hasTo('erika@example.com'));
    $newToken = issuedToken();

    expect($newToken)->not->toBe($oldToken);
    $this->get(route('form.confirm.show', $oldToken))
        ->assertInertia(fn (Assert $page): Assert => $page->component('Forms/Confirm')->where('status', 'invalid'));
    $this->get(route('form.confirm.show', $newToken))
        ->assertInertia(fn (Assert $page): Assert => $page->component('Forms/Confirm')->where('status', 'pending'));
});

test('an already confirmed submission gets no new confirmation mail', function (): void {
    [$form, $email] = confirmedForm();
    submitConfirmedForm($form, $email);
    $submission = FormSubmission::sole();
    $submission->update(['confirmed_at' => now()]);
    actAsGroupAdmin($form);

    $this->post(route('form-submissions.resend-confirmation', $submission))
        ->assertSessionHasErrors('error');

    Mail::assertQueued(FormSubmissionConfirmation::class, 1);
});

test('members of other groups cannot resend the confirmation mail', function (): void {
    [$form, $email] = confirmedForm();
    submitConfirmedForm($form, $email);
    $other = FormDefinition::factory()->create();
    actAsGroupAdmin($other);

    $this->post(route('form-submissions.resend-confirmation', FormSubmission::sole()))
        ->assertForbidden();

    Mail::assertQueued(FormSubmissionConfirmation::class, 1);
});

test('confirmation mails are limited per address', function (): void {
    config(['forms.confirmation.max_per_address_per_hour' => 2, 'forms.confirmation.max_per_ip_per_hour' => 100]);
    [$form, $email] = confirmedForm();

    submitConfirmedForm($form, $email)->assertOk();
    submitConfirmedForm($form, $email, 'ERIKA@example.com')->assertOk();
    submitConfirmedForm($form, $email)->assertTooManyRequests();
    submitConfirmedForm($form, $email, 'max@example.com')->assertOk();

    expect(FormSubmission::count())->toBe(3);
    Mail::assertQueuedCount(3);
});

test('confirmation mails are limited per ip', function (): void {
    config(['forms.confirmation.max_per_address_per_hour' => 100, 'forms.confirmation.max_per_ip_per_hour' => 2]);
    [$form, $email] = confirmedForm();

    submitConfirmedForm($form, $email, 'a@example.com')->assertOk();
    submitConfirmedForm($form, $email, 'b@example.com')->assertOk();
    submitConfirmedForm($form, $email, 'c@example.com')->assertTooManyRequests();

    expect(FormSubmission::count())->toBe(2);
    Mail::assertQueuedCount(2);
});

test('forms without confirmation are not limited per address', function (): void {
    config(['forms.confirmation.max_per_address_per_hour' => 1]);
    [$form, $email] = confirmedForm();
    $form->update(['requires_email_confirmation' => false]);

    submitConfirmedForm($form, $email)->assertOk();
    submitConfirmedForm($form, $email)->assertOk();

    Mail::assertNothingQueued();
});

test('the prune command does nothing unless it is configured', function (): void {
    [$form, $email] = confirmedForm();
    submitConfirmedForm($form, $email);

    $this->travel(60)->days();
    $this->artisan('form-submissions:prune-unconfirmed')->assertSuccessful();

    expect(FormSubmission::count())->toBe(1);
});

test('the prune command only deletes old unconfirmed submissions with an expired link', function (): void {
    config(['forms.confirmation.prune_after_days' => 7]);
    Storage::fake(ImageStorage::DISK);
    Storage::fake(ImageStorage::CACHE_DISK);
    [$form, $email] = confirmedForm();

    submitConfirmedForm($form, $email, 'old@example.com');
    $old = FormSubmission::latest('id')->first();
    Storage::disk(ImageStorage::DISK)->put('form-images/'.$old->uuid.'/image.jpg', 'image');

    submitConfirmedForm($form, $email, 'confirmed@example.com');
    $confirmed = FormSubmission::latest('id')->first();
    app(FormSubmissionConfirmationService::class)->confirm($confirmed);

    $form->update(['requires_email_confirmation' => false]);
    submitConfirmedForm($form, $email, 'plain@example.com');
    $plain = FormSubmission::latest('id')->first();

    $this->travel(31)->days();

    $form->update(['requires_email_confirmation' => true]);
    submitConfirmedForm($form, $email, 'young@example.com');
    $young = FormSubmission::latest('id')->first();

    $this->artisan('form-submissions:prune-unconfirmed')->assertSuccessful();

    expect(FormSubmission::pluck('id')->all())->toEqualCanonicalizing([$confirmed->id, $plain->id, $young->id])
        ->and(SubmissionField::where('form_submission_id', $old->id)->exists())->toBeFalse();
    Storage::disk(ImageStorage::DISK)->assertMissing('form-images/'.$old->uuid.'/image.jpg');
});

/**
 * @return array{FormDefinitionToAdvice, array<string, mixed>}
 */
function adviceFormInput(?bool $ticked): array
{
    $adviceCreator = FormDefinitionToAdvice::factory()->create();

    $input = [
        $adviceCreator->firstNameField->uuid => 'Erika',
        $adviceCreator->lastNameField->uuid => 'Mustermann',
        $adviceCreator->emailField->uuid => 'erika@example.com',
        $adviceCreator->phoneField->uuid => '06151 123456',
        $adviceCreator->addressField->uuid => ['street' => 'Hauptstraße', 'street_number' => '1', 'city' => 'Darmstadt', 'zip' => '64283'],
        $adviceCreator->adviceTypeField->uuid => AdviceType::Virtual->value,
    ];

    if ($ticked !== null) {
        $condition = $adviceCreator->formDefinition->fields()->create(['type' => FieldType::CHECKBOX, 'label' => 'Beratung gewünscht', 'required' => false, 'sort_order' => 20]);
        $condition->options()->create(['label' => 'Ja', 'value' => 'yes', 'sort_order' => 0]);
        $adviceCreator->conditionField()->associate($condition)->save();
        $input[$condition->uuid] = $ticked ? ['yes'] : [];
    }

    return [$adviceCreator, $input];
}

test('an advice is only created when its condition is ticked', function (?bool $ticked, int $advices): void {
    [$adviceCreator, $input] = adviceFormInput($ticked);

    $this->post(route('form.submit', $adviceCreator->formDefinition), $input)->assertSessionHasNoErrors();

    expect(Advice::count())->toBe($advices)
        ->and(FormSubmission::count())->toBe(1);
})->with([
    'ticked' => [true, 1],
    'not ticked' => [false, 0],
    'without condition' => [null, 1],
]);
