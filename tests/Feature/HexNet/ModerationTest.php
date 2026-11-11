<?php

use App\Enums\MapPointDeletedBy;
use App\Enums\PublicConsent;
use App\Events\MapPointCreatedByFormSubmission;
use App\HexNet\Enums\HexNetModerationAction;
use App\HexNet\Enums\HexNetStatus;
use App\HexNet\HexNetDefaults;
use App\HexNet\Mail\HexNetStoneCreated;
use App\HexNet\Models\HexNet;
use App\HexNet\Services\HexNetGridService;
use App\HexNet\Services\HexNetModeration;
use App\HexNet\Services\HexNetSetupService;
use App\Mail\FormSubmissionConfirmation;
use App\Models\FormSubmission;
use App\Models\Group;
use App\Models\MapPoint;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\AutoAttachesFormEmbedToken;

uses(RefreshDatabase::class, AutoAttachesFormEmbedToken::class);

beforeEach(function (): void {
    Storage::fake('public');
    Mail::fake();
    $this->group = Group::factory()->create();
    $this->admin = User::factory()->create(['is_admin' => false]);
    $this->member = User::factory()->create(['is_admin' => false]);
    $this->group->users()->attach($this->admin, ['is_admin' => true]);
    $this->group->users()->attach($this->member, ['is_admin' => false]);
    $this->hexNet = HexNet::factory()->active()->withPlayArea()->create(['group_id' => $this->group->id, 'name' => 'Darmstadt 2027']);
    $this->category = app(HexNetSetupService::class)->createCategories($this->hexNet, 'Trittsteine', 'Trittsteine 2027', array_slice(HexNetDefaults::MEASURES, 0, 2));
    $this->form = app(HexNetSetupService::class)->createForm($this->hexNet->refresh());
});

function moderationJoin(object $test, string $title = 'Garten Müller'): MapPoint
{
    $fields = $test->form->fields()->get()->keyBy('label');
    $measure = $test->category->parent->characteristics()->first();

    $test->post(route('form.submit', $test->form), [
        $fields['Titel']->uuid => $title,
        $fields['Beschreibung']->uuid => 'Mit Teich',
        $fields['Standort']->uuid => ['lat' => 49.8728, 'lng' => 8.6512],
        $fields['E-Mail-Adresse']->uuid => 'erika@example.org',
        $fields['Maßnahmen']->uuid => [$measure->uuid],
        '_consent_show' => true,
        '_consent_details' => true,
    ])->assertSessionHasNoErrors();

    $token = basename((string) parse_url(Mail::queued(FormSubmissionConfirmation::class)->last()->confirmationUrl, PHP_URL_PATH));
    $test->post(route('form.confirm', $token));

    return MapPoint::where('title', $title)->sole();
}

/**
 * @param  array<string, mixed>  $attributes
 */
function moderationPoint(object $test, array $attributes = []): MapPoint
{
    return MapPoint::factory()->create([
        'group_id' => $test->group->id,
        'category_id' => $test->category->id,
        'lat' => 49.8728,
        'lng' => 8.6512,
        'published' => false,
        ...$attributes,
    ]);
}

function moderationUrl(object $test, MapPoint $point, HexNetModerationAction $action, ?User $admin = null): string
{
    return app(HexNetModeration::class)->url($test->hexNet, $point, $admin ?? $test->admin, $action);
}

test('a new stepping stone of an active campaign mails the admins of its group', function (): void {
    $point = moderationJoin($this);

    Mail::assertQueued(HexNetStoneCreated::class, 1);
    $mail = Mail::queued(HexNetStoneCreated::class)->sole();

    expect($mail->hasTo($this->admin->email))->toBeTrue()
        ->and($mail->hasTo($this->member->email))->toBeFalse()
        ->and($mail->point->campaign)->toBe($this->hexNet->title)
        ->and($mail->point->title)->toBe('Garten Müller')
        ->and($mail->point->measures)->toBe([HexNetDefaults::MEASURES[0]['name']])
        ->and($mail->point->consent)->toStartWith('Mit Angaben:')
        ->and($mail->point->backend_url)->toBe(route('mappoints.edit', $point))
        ->and(array_keys($mail->actions))->toBe(['Freigeben', 'Löschen']);

    $mail->assertSeeInHtml('Neuer Trittstein');
});

test('with auto publish the mail offers to withdraw instead of to publish', function (): void {
    $this->hexNet->update(['auto_publish' => true]);

    moderationJoin($this);

    expect(array_keys(Mail::queued(HexNetStoneCreated::class)->sole()->actions))->toBe(['Zurückziehen', 'Löschen']);
});

test('no mail for a campaign that is not running', function (): void {
    $this->hexNet->update(['status' => HexNetStatus::DRAFT]);

    moderationJoin($this);

    Mail::assertNotQueued(HexNetStoneCreated::class);
});

test('only creating a stepping stone sends a mail, later changes such as additions do not', function (): void {
    $point = moderationPoint($this);
    $submission = FormSubmission::factory()->create(['form_definition_id' => $this->form->id]);

    event(new MapPointCreatedByFormSubmission($point, $submission));
    Mail::assertQueued(HexNetStoneCreated::class, 1);

    $point->update(['description' => 'Jetzt mit Fotos']);
    Mail::assertQueued(HexNetStoneCreated::class, 1);
});

test('opening a link changes nothing, confirming publishes', function (): void {
    $point = moderationPoint($this);
    $url = moderationUrl($this, $point, HexNetModerationAction::PUBLISH);

    $this->get($url)->assertOk()->assertInertia(fn ($page) => $page
        ->component('HexNet/Moderation')
        ->where('point.title', $point->title)
        ->where('action', 'freigeben')
        ->where('done', false));
    expect($point->fresh()->published)->toBeFalse();

    $this->post($url)->assertOk()->assertInertia(fn ($page) => $page->where('done', true)->where('point.published', true));
    expect($point->fresh()->published)->toBeTrue();
});

test('withdrawing hides the stepping stone but it still counts', function (): void {
    $point = moderationPoint($this, ['published' => true]);

    $this->post(moderationUrl($this, $point, HexNetModerationAction::WITHDRAW))->assertOk();

    expect($point->fresh()->published)->toBeFalse()
        ->and(array_sum(app(HexNetGridService::class)->counts($this->hexNet->fresh())))->toBe(1);
});

test('deleting removes the stepping stone and notes it at the submission', function (): void {
    $submission = FormSubmission::factory()->create(['form_definition_id' => $this->form->id]);
    $point = moderationPoint($this, ['public_consent' => PublicConsent::REFUSED]);
    $point->forceFill(['pointable_type' => $submission->getMorphClass(), 'pointable_id' => $submission->id])->save();

    $this->post(moderationUrl($this, $point, HexNetModerationAction::DELETE))->assertOk()
        ->assertInertia(fn ($page) => $page->where('done', true)->where('point.consent', 'Nur mitzählen, nicht öffentlich'));

    expect(MapPoint::find($point->id))->toBeNull()
        ->and($submission->fresh()->map_point_deleted_by)->toBe(MapPointDeletedBy::ADMIN);
});

test('only admins of the campaign group can act, also with a signed link', function (): void {
    $point = moderationPoint($this);

    $this->post(moderationUrl($this, $point, HexNetModerationAction::PUBLISH, $this->member))
        ->assertRedirect(route('mappoints.edit', $point));
    expect($point->fresh()->published)->toBeFalse();

    $url = moderationUrl($this, $point, HexNetModerationAction::DELETE);
    $this->group->users()->updateExistingPivot($this->admin->id, ['is_admin' => false]);

    $this->post($url)->assertRedirect(route('mappoints.edit', $point));
    expect(MapPoint::find($point->id))->not->toBeNull();
});

test('expired or tampered links lead to the stepping stone in the backend', function (): void {
    $point = moderationPoint($this);
    $url = moderationUrl($this, $point, HexNetModerationAction::PUBLISH);

    $this->post(str_replace('freigeben', 'loeschen', $url))->assertRedirect(route('mappoints.edit', $point));
    expect(MapPoint::find($point->id))->not->toBeNull();

    $this->travel(HexNetModeration::LINK_DAYS + 1)->days();
    $this->get($url)->assertRedirect(route('mappoints.edit', $point));
    $this->post($url)->assertRedirect(route('mappoints.edit', $point));
    expect($point->fresh()->published)->toBeFalse();
});

test('a link to a deleted stepping stone leads to the list', function (): void {
    $point = moderationPoint($this);
    $url = moderationUrl($this, $point, HexNetModerationAction::PUBLISH);
    $point->delete();

    $this->get($url)->assertRedirect(route('mappoints.index'));
});
