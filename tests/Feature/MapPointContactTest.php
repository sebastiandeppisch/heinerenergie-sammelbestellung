<?php

use App\Mail\MapPointContact\ContactRequestConfirmation;
use App\Mail\MapPointContact\ContactRequestForward;
use App\Models\Group;
use App\Models\MapEmbed;
use App\Models\MapPoint;
use App\Models\MapPointCategory;
use App\Models\MapPointContactRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Mail::fake();

    $this->group = Group::factory()->create(['name' => 'Solar-Initiative Darmstadt']);
    $this->category = MapPointCategory::factory()->for($this->group)->create();
    $this->embed = MapEmbed::factory()->for($this->group)->create(['show_table' => true]);
    $this->embed->mapPointCategories()->sync([$this->category->id]);

    $this->point = MapPoint::factory()->for($this->group)->create([
        'title' => 'Garten mit Balkonkraftwerk',
        'published' => true,
        'category_id' => $this->category->id,
        'contact_email' => 'owner@example.org',
        'contact_email_verified_at' => now(),
        'contact_enabled' => true,
    ]);
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function contactPayload(array $overrides = []): array
{
    return [
        'name' => 'Erika Muster',
        'email' => 'erika@example.org',
        'message' => 'Darf ich mir deinen Garten anschauen?',
        'privacy' => true,
        'website' => '',
        ...$overrides,
    ];
}

/**
 * @param  array<string, mixed>  $overrides
 * @return TestResponse<Response>
 */
function sendContactRequest(TestCase $test, MapEmbed $embed, MapPoint $point, array $overrides = [], string $ip = '10.0.0.1'): TestResponse
{
    return $test->withServerVariables(['REMOTE_ADDR' => $ip])
        ->post(route('map-contact.store', [$embed, $point]), contactPayload($overrides));
}

function confirmationToken(): string
{
    $mail = Mail::queued(ContactRequestConfirmation::class)->last();

    return basename((string) parse_url($mail->confirmationUrl, PHP_URL_PATH));
}

test('has_contact is only true for a published point with enabled contact and a confirmed address', function (array $attributes, bool $expected): void {
    $this->point->update($attributes);

    $this->get(route('map.public', $this->embed))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where("pointsByCategory.{$this->category->uuid}.0.has_contact", $expected)
        );
})->with([
    'everything set' => [[], true],
    'contact disabled' => [['contact_enabled' => false], false],
    'address not confirmed' => [['contact_email_verified_at' => null], false],
    'no address' => [['contact_email' => null, 'contact_email_verified_at' => null], false],
]);

test('unpublished points are not on the map and cannot be contacted', function (): void {
    $this->point->update(['published' => false]);

    $this->get(route('map-contact.create', [$this->embed, $this->point]))->assertNotFound();
    sendContactRequest($this, $this->embed, $this->point)->assertNotFound();
});

test('the contact address appears in no public output', function (): void {
    $response = $this->get(route('map.public', $this->embed));

    $response->assertOk()
        ->assertDontSee('owner@example.org')
        ->assertInertia(fn ($page) => $page
            ->missing("pointsByCategory.{$this->category->uuid}.0.contact_email")
            ->missing("pointsByCategory.{$this->category->uuid}.0.group_id")
        );

    $this->get(route('map-contact.create', [$this->embed, $this->point]))
        ->assertOk()
        ->assertDontSee('owner@example.org')
        ->assertInertia(fn ($page) => $page->component('MapPointContact/Create'));

    sendContactRequest($this, $this->embed, $this->point)->assertDontSee('owner@example.org');

    $this->get(route('map-contact.unsubscribe', $this->point))->assertDontSee('owner@example.org');
});

test('a point of another embed cannot be contacted through this embed', function (): void {
    $otherEmbed = MapEmbed::factory()->for($this->group)->create();

    $this->get(route('map-contact.create', [$otherEmbed, $this->point]))->assertNotFound();
});

test('nothing is forwarded before the sender confirms', function (): void {
    sendContactRequest($this, $this->embed, $this->point)
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('MapPointContact/Sent'));

    Mail::assertQueued(ContactRequestConfirmation::class, fn ($mail) => $mail->hasTo('erika@example.org'));
    Mail::assertNotQueued(ContactRequestForward::class);

    $contactRequest = MapPointContactRequest::sole();
    expect($contactRequest->confirmed_at)->toBeNull()
        ->and($contactRequest->confirmation_token_hash)->toBe(hash_hmac('sha256', confirmationToken(), config('app.key')));

    $this->get(route('map-contact.confirm.show', confirmationToken()))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('MapPointContact/Confirm')->where('status', 'pending'));

    Mail::assertNotQueued(ContactRequestForward::class);
    expect($contactRequest->fresh()->confirmed_at)->toBeNull();
});

test('confirming forwards the request exactly once', function (): void {
    sendContactRequest($this, $this->embed, $this->point);
    $token = confirmationToken();

    $this->post(route('map-contact.confirm', $token))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('status', 'forwarded'));

    $this->post(route('map-contact.confirm', $token))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('status', 'already_confirmed'));

    Mail::assertQueuedCount(2);
    Mail::assertQueued(ContactRequestForward::class, 1);
    expect(MapPointContactRequest::sole()->forwarded_at)->not->toBeNull();
});

test('an expired confirmation link is rejected', function (): void {
    sendContactRequest($this, $this->embed, $this->point);
    $token = confirmationToken();

    $this->travel(25)->hours();

    $this->post(route('map-contact.confirm', $token))
        ->assertInertia(fn ($page) => $page->where('status', 'expired'));

    Mail::assertNotQueued(ContactRequestForward::class);
});

test('an unknown confirmation link is invalid', function (): void {
    $this->get(route('map-contact.confirm.show', 'unknown'))
        ->assertInertia(fn ($page) => $page->where('status', 'invalid')->where('token', null));
});

test('a request is not forwarded once the owner switched contact off', function (): void {
    sendContactRequest($this, $this->embed, $this->point);
    $this->point->update(['contact_enabled' => false]);

    $this->post(route('map-contact.confirm', confirmationToken()))
        ->assertInertia(fn ($page) => $page->where('status', 'unavailable'));

    Mail::assertNotQueued(ContactRequestForward::class);
});

test('the forwarded mail comes from the system, replies go to the sender and it carries no token', function (): void {
    sendContactRequest($this, $this->embed, $this->point);
    $token = confirmationToken();
    $this->post(route('map-contact.confirm', $token));

    $mail = Mail::queued(ContactRequestForward::class)->sole();

    expect($mail->hasTo('owner@example.org'))->toBeTrue()
        ->and($mail->hasReplyTo('erika@example.org', 'Erika Muster'))->toBeTrue()
        ->and($mail->from)->toBe([]);

    $mail->assertHasSubject('Kontaktanfrage zu deinem Eintrag „Garten mit Balkonkraftwerk“');
    $mail->assertSeeInHtml('Erika Muster');
    $mail->assertSeeInHtml('Darf ich mir deinen Garten anschauen?');
    $mail->assertSeeInHtml('Solar-Initiative Darmstadt');
    $mail->assertSeeInHtml('Deine Adresse sieht die Person erst, wenn du antwortest.');
    $mail->assertSeeInHtml(route('map-contact.unsubscribe', $this->point));
    $mail->assertDontSeeInHtml($token);
    $mail->assertDontSeeInHtml('/map-contact/confirm/');
    $mail->assertDontSeeInHtml('/map-contact/manage/');
    $mail->assertDontSeeInHtml('/map-contact/verify/');
});

test('the sender message cannot inject markup into the forwarded mail', function (): void {
    sendContactRequest($this, $this->embed, $this->point, ['message' => "<a href=\"https://evil.example\">klick</a>\n\n[Link](https://evil.example)"]);
    $this->post(route('map-contact.confirm', confirmationToken()));

    $mail = Mail::queued(ContactRequestForward::class)->sole();

    $mail->assertDontSeeInHtml('<a href="https://evil.example"', false);
    $mail->assertSeeInHtml('[Link](https://evil.example)', false);
});

test('the confirmation mail names the initiative', function (): void {
    sendContactRequest($this, $this->embed, $this->point);

    Mail::queued(ContactRequestConfirmation::class)->sole()->assertSeeInHtml('Solar-Initiative Darmstadt');
});

test('a filled honeypot discards the request silently', function (): void {
    sendContactRequest($this, $this->embed, $this->point, ['website' => 'https://spam.example'])
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('MapPointContact/Sent'));

    expect(MapPointContactRequest::count())->toBe(0);
    Mail::assertNothingQueued();
});

test('the privacy policy has to be accepted and the message is limited', function (): void {
    sendContactRequest($this, $this->embed, $this->point, ['privacy' => false, 'message' => str_repeat('a', 2001)])
        ->assertSessionHasErrors(['privacy', 'message']);

    expect(MapPointContactRequest::count())->toBe(0);
});

test('the rate limit per IP also limits confirmation mails', function (): void {
    foreach (range(1, 5) as $i) {
        sendContactRequest($this, $this->embed, $this->point, ['email' => "sender{$i}@example.org"])->assertOk();
    }

    sendContactRequest($this, $this->embed, $this->point, ['email' => 'sender6@example.org'])->assertSessionHasErrors('message');

    Mail::assertQueued(ContactRequestConfirmation::class, 5);
    expect(MapPointContactRequest::count())->toBe(5);
});

test('the rate limit per sender address applies across IPs', function (): void {
    foreach (range(1, 3) as $i) {
        sendContactRequest($this, $this->embed, $this->point, ['email' => 'Erika@Example.org'], "10.0.1.{$i}")->assertOk();
    }

    sendContactRequest($this, $this->embed, $this->point, ['email' => 'erika@example.org'], '10.0.1.9')->assertSessionHasErrors('message');

    Mail::assertQueued(ContactRequestConfirmation::class, 3);
});

test('the rate limit per point applies across senders and IPs', function (): void {
    foreach (range(1, 10) as $i) {
        sendContactRequest($this, $this->embed, $this->point, ['email' => "s{$i}@example.org"], "10.0.2.{$i}")->assertOk();
    }

    sendContactRequest($this, $this->embed, $this->point, ['email' => 's11@example.org'], '10.0.2.11')->assertSessionHasErrors('message');

    Mail::assertQueued(ContactRequestConfirmation::class, 10);
});

test('the rate limits come from the config', function (): void {
    config(['map_contact.limits.per_ip_per_hour' => 1]);

    sendContactRequest($this, $this->embed, $this->point)->assertOk();
    sendContactRequest($this, $this->embed, $this->point, ['email' => 'other@example.org'])->assertSessionHasErrors('message');
});
