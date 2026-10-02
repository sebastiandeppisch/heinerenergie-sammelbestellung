<?php

use App\Enums\AdviceType;
use App\Enums\FieldType;
use App\Mail\AdviceCreated;
use App\Mail\FormSubmissionConfirmation;
use App\Models\Advice;
use App\Models\FormDefinition;
use App\Models\FormDefinitionToAdvice;
use App\Models\FormDefinitionToMapPoint;
use App\Models\FormSubmission;
use App\Models\MapPoint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\AutoAttachesFormEmbedToken;

uses(RefreshDatabase::class, AutoAttachesFormEmbedToken::class);

beforeEach(function (): void {
    Mail::fake();
    Http::fake();
});

/**
 * A form with every target: an advice that is only created when a checkbox is ticked, and a map point.
 *
 * @return array{FormDefinition, array<string, mixed>}
 */
function formWithAllTargets(bool $requiresConfirmation): array
{
    $adviceCreator = FormDefinitionToAdvice::factory()->create();
    $form = $adviceCreator->formDefinition;
    $form->update(['requires_email_confirmation' => $requiresConfirmation, 'success_message' => 'Danke für deine Einsendung']);

    $wantsAdvice = $form->fields()->create(['type' => FieldType::CHECKBOX, 'label' => 'Ich möchte eine Beratung', 'required' => false, 'sort_order' => 20]);
    $wantsAdvice->options()->create(['label' => 'Ja', 'value' => 'yes', 'sort_order' => 0]);
    $adviceCreator->conditionField()->associate($wantsAdvice)->save();

    $title = $form->fields()->create(['type' => FieldType::TEXT, 'label' => 'Titel', 'required' => true, 'sort_order' => 21]);
    $description = $form->fields()->create(['type' => FieldType::TEXTAREA, 'label' => 'Beschreibung', 'required' => false, 'sort_order' => 22]);
    $coordinate = $form->fields()->create(['type' => FieldType::GEO_COORDINATE, 'label' => 'Standort', 'required' => true, 'sort_order' => 23]);

    FormDefinitionToMapPoint::factory()->create([
        'form_definition_id' => $form->id,
        'title_field_id' => $title->id,
        'description_field_id' => $description->id,
        'coordinate_field_id' => $coordinate->id,
    ]);

    $input = [
        $adviceCreator->firstNameField->uuid => 'Erika',
        $adviceCreator->lastNameField->uuid => 'Mustermann',
        $adviceCreator->emailField->uuid => 'erika@example.com',
        $adviceCreator->phoneField->uuid => '06151 123456',
        $adviceCreator->addressField->uuid => ['street' => 'Hauptstraße', 'street_number' => '1', 'city' => 'Darmstadt', 'zip' => '64283'],
        $adviceCreator->adviceTypeField->uuid => AdviceType::Virtual->value,
        $wantsAdvice->uuid => ['yes'],
        $title->uuid => 'Balkonkraftwerk',
        $description->uuid => 'Auf dem Balkon',
        $coordinate->uuid => ['lat' => 49.8728, 'lng' => 8.6510],
    ];

    return [$form->fresh(), $input];
}

function confirmationToken(): string
{
    $token = null;

    Mail::assertQueued(FormSubmissionConfirmation::class, function (FormSubmissionConfirmation $mail) use (&$token): bool {
        $token = basename(parse_url($mail->confirmationUrl, PHP_URL_PATH));

        return $mail->hasTo('erika@example.com');
    });

    return $token;
}

function assertTargetsCreatedOnce(): void
{
    expect(Advice::count())->toBe(1)
        ->and(MapPoint::count())->toBe(1)
        ->and(FormSubmission::sole()->advice_id)->toBe(Advice::sole()->id);

    Mail::assertQueued(AdviceCreated::class, 1);
}

test('without confirmation all targets run when the form is submitted', function (): void {
    [$form, $input] = formWithAllTargets(requiresConfirmation: false);

    $this->post(route('form.submit', $form), $input)
        ->assertSessionHasNoErrors()
        ->assertInertia(fn (Assert $page): Assert => $page
            ->component('Forms/Submitted')
            ->where('formDefinition.success_message', 'Danke für deine Einsendung')
        );

    assertTargetsCreatedOnce();
    Mail::assertNotQueued(FormSubmissionConfirmation::class);

    $submission = FormSubmission::sole();
    expect($submission->confirmed_at)->toBeNull()
        ->and($submission->confirmation_token_hash)->toBeNull()
        ->and($submission->isAwaitingConfirmation())->toBeFalse();
});

test('with confirmation the targets run exactly once after the address is confirmed', function (): void {
    [$form, $input] = formWithAllTargets(requiresConfirmation: true);

    $this->post(route('form.submit', $form), $input)
        ->assertSessionHasNoErrors()
        ->assertInertia(fn (Assert $page): Assert => $page->component('Forms/ConfirmationPending'));

    expect(Advice::count())->toBe(0)
        ->and(MapPoint::count())->toBe(0)
        ->and(FormSubmission::sole()->isAwaitingConfirmation())->toBeTrue();
    Mail::assertNotQueued(AdviceCreated::class);

    $token = confirmationToken();

    $this->get(route('form.confirm.show', $token))
        ->assertInertia(fn (Assert $page): Assert => $page->component('Forms/Confirm')->where('status', 'pending'));

    expect(Advice::count())->toBe(0);

    $this->post(route('form.confirm', $token))
        ->assertInertia(fn (Assert $page): Assert => $page
            ->component('Forms/Submitted')
            ->where('formDefinition.success_message', 'Danke für deine Einsendung')
            ->has('notices', 0)
        );

    assertTargetsCreatedOnce();
    expect(FormSubmission::sole()->confirmed_at)->not->toBeNull();

    $this->post(route('form.confirm', $token))
        ->assertInertia(fn (Assert $page): Assert => $page->component('Forms/Confirm')->where('status', 'already_confirmed'));
    $this->get(route('form.confirm.show', $token))
        ->assertInertia(fn (Assert $page): Assert => $page->component('Forms/Confirm')->where('status', 'already_confirmed'));

    assertTargetsCreatedOnce();
});
