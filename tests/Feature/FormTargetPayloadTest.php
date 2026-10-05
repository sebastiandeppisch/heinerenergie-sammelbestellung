<?php

use App\Enums\AdviceType;
use App\Enums\FieldType;
use App\Events\MapPointCreatedByFormSubmission;
use App\Mail\FormSubmissionConfirmation;
use App\Models\Advice;
use App\Models\FormDefinition;
use App\Models\FormDefinitionToAdvice;
use App\Models\FormDefinitionToMapPoint;
use App\Models\FormSubmission;
use App\Models\MapPoint;
use App\Models\MapPointCategory;
use App\Models\User;
use App\Notifications\SystemErrorNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\AutoAttachesFormEmbedToken;

uses(RefreshDatabase::class, AutoAttachesFormEmbedToken::class);

beforeEach(function (): void {
    Mail::fake();
    Http::fake();
    Notification::fake();
});

/**
 * A form with an advice and a map point target, whose points go to the given category.
 *
 * @return array{FormDefinitionToAdvice, FormDefinitionToMapPoint, array<string, mixed>}
 */
function formWithFrozenTargets(bool $requiresConfirmation, ?MapPointCategory $category = null): array
{
    $adviceCreator = FormDefinitionToAdvice::factory()->create();
    $form = $adviceCreator->formDefinition;
    $form->update(['requires_email_confirmation' => $requiresConfirmation]);

    $title = $form->fields()->create(['type' => FieldType::TEXT, 'label' => 'Titel', 'required' => true, 'sort_order' => 21]);
    $description = $form->fields()->create(['type' => FieldType::TEXTAREA, 'label' => 'Beschreibung', 'required' => false, 'sort_order' => 22]);
    $coordinate = $form->fields()->create(['type' => FieldType::GEO_COORDINATE, 'label' => 'Standort', 'required' => true, 'sort_order' => 23]);

    $mapPointCreator = FormDefinitionToMapPoint::factory()->create([
        'form_definition_id' => $form->id,
        'title_field_id' => $title->id,
        'description_field_id' => $description->id,
        'coordinate_field_id' => $coordinate->id,
    ]);
    $mapPointCreator->category()->associate($category)->save();

    $input = [
        $adviceCreator->firstNameField->uuid => 'Erika',
        $adviceCreator->lastNameField->uuid => 'Mustermann',
        $adviceCreator->emailField->uuid => 'erika@example.com',
        $adviceCreator->phoneField->uuid => '06151 123456',
        $adviceCreator->addressField->uuid => ['street' => 'Hauptstraße', 'street_number' => '1', 'city' => 'Darmstadt', 'zip' => '64283'],
        $adviceCreator->adviceTypeField->uuid => AdviceType::Virtual->value,
        $title->uuid => 'Balkonkraftwerk',
        $description->uuid => 'Auf dem Balkon',
        $coordinate->uuid => ['lat' => 49.8728, 'lng' => 8.6510],
    ];

    return [$adviceCreator->fresh(), $mapPointCreator->fresh(), $input];
}

/**
 * @param  array<string, mixed>  $input
 * @return TestResponse<Response>
 */
function submitFrozenForm(FormDefinition $form, array $input): TestResponse
{
    return test()->post(route('form.submit', $form), $input)->assertSessionHasNoErrors();
}

/**
 * @return TestResponse<Response>
 */
function confirmLastSubmission(): TestResponse
{
    $mail = Mail::queued(FormSubmissionConfirmation::class)->last();

    return test()->post(route('form.confirm', basename(parse_url($mail->confirmationUrl, PHP_URL_PATH))));
}

function addCondition(FormDefinitionToAdvice $adviceCreator): string
{
    $condition = $adviceCreator->formDefinition->fields()->create(['type' => FieldType::CHECKBOX, 'label' => 'Beratung gewünscht', 'required' => false, 'sort_order' => 30]);
    $condition->options()->create(['label' => 'Ja', 'value' => 'yes', 'sort_order' => 0]);
    $adviceCreator->conditionField()->associate($condition)->save();

    return $condition->uuid;
}

test('a remapped field does not change the advice of a waiting submission', function (): void {
    [$adviceCreator, , $input] = formWithFrozenTargets(requiresConfirmation: true);
    submitFrozenForm($adviceCreator->formDefinition, $input);

    $newField = $adviceCreator->formDefinition->fields()->create(['type' => FieldType::TEXT, 'label' => 'Rufname', 'required' => false, 'sort_order' => 30]);
    $adviceCreator->firstNameField()->associate($newField)->save();

    confirmLastSubmission()->assertInertia(fn (Assert $page): Assert => $page->component('Forms/Submitted'));

    expect(Advice::sole()->first_name)->toBe('Erika');
});

test('a condition added after submitting does not apply to the waiting submission', function (): void {
    [$adviceCreator, , $input] = formWithFrozenTargets(requiresConfirmation: true);
    submitFrozenForm($adviceCreator->formDefinition, $input);

    addCondition($adviceCreator);
    confirmLastSubmission();

    expect(Advice::count())->toBe(1);
});

test('a condition not ticked when submitting still applies after it was removed', function (): void {
    [$adviceCreator, , $input] = formWithFrozenTargets(requiresConfirmation: true);
    $condition = addCondition($adviceCreator);
    submitFrozenForm($adviceCreator->formDefinition, [...$input, $condition => []]);

    $adviceCreator->conditionField()->dissociate()->save();
    confirmLastSubmission();

    expect(Advice::count())->toBe(0)
        ->and(MapPoint::count())->toBe(1)
        ->and(FormSubmission::sole()->targets_failed_at)->toBeNull();
});

test('a changed category does not move the point of a waiting submission', function (): void {
    $original = MapPointCategory::factory()->create();
    [$adviceCreator, $mapPointCreator, $input] = formWithFrozenTargets(requiresConfirmation: true, category: $original);
    submitFrozenForm($adviceCreator->formDefinition, $input);

    $mapPointCreator->category()->associate(MapPointCategory::factory()->create())->save();
    confirmLastSubmission();

    expect(MapPoint::sole()->category_id)->toBe($original->id);
});

test('a deleted category confirms the submission, creates no target and notifies the system admins', function (): void {
    $admin = User::factory()->admin()->create();
    $advisor = User::factory()->create(['is_admin' => false]);
    $category = MapPointCategory::factory()->create();
    [$adviceCreator, , $input] = formWithFrozenTargets(requiresConfirmation: true, category: $category);
    submitFrozenForm($adviceCreator->formDefinition, $input);

    $category->delete();

    confirmLastSubmission()->assertInertia(fn (Assert $page): Assert => $page
        ->component('Forms/Submitted')
        ->has('notices', 0)
    );

    $submission = FormSubmission::sole();
    expect($submission->confirmed_at)->not->toBeNull()
        ->and($submission->targets_failed_at)->not->toBeNull()
        ->and($submission->targets_error)->not->toBeEmpty()
        ->and($submission->advice_id)->toBeNull()
        ->and(Advice::count())->toBe(0)
        ->and(MapPoint::count())->toBe(0);

    Notification::assertSentTo($admin, SystemErrorNotification::class, fn (SystemErrorNotification $notification): bool => $notification->formSubmission?->is($submission) === true);
    Notification::assertNotSentTo($advisor, SystemErrorNotification::class);
});

test('a failing target without confirmation keeps the submission and notifies the system admins', function (): void {
    $admin = User::factory()->admin()->create();
    [$adviceCreator, , $input] = formWithFrozenTargets(requiresConfirmation: false);
    Event::listen(MapPointCreatedByFormSubmission::class, fn () => throw new RuntimeException('Kartenpunkt kaputt'));

    submitFrozenForm($adviceCreator->formDefinition, $input)
        ->assertInertia(fn (Assert $page): Assert => $page->component('Forms/Submitted'));

    $submission = FormSubmission::sole();
    expect($submission->targets_failed_at)->not->toBeNull()
        ->and($submission->targets_error)->toBe('Kartenpunkt kaputt')
        ->and($submission->submissionFields()->count())->toBeGreaterThan(0)
        ->and(Advice::count())->toBe(0)
        ->and(MapPoint::count())->toBe(0);

    Notification::assertSentTo($admin, SystemErrorNotification::class);
});

test('a waiting submission without a frozen payload reads its targets when confirmed', function (): void {
    [$adviceCreator, , $input] = formWithFrozenTargets(requiresConfirmation: true);
    submitFrozenForm($adviceCreator->formDefinition, $input);
    FormSubmission::query()->update(['target_payload' => null]);

    addCondition($adviceCreator);
    confirmLastSubmission();

    $submission = FormSubmission::sole();
    expect($submission->confirmed_at)->not->toBeNull()
        ->and($submission->targets_failed_at)->toBeNull()
        ->and(Advice::count())->toBe(0)
        ->and(MapPoint::count())->toBe(1);
});
