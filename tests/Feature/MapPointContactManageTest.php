<?php

use App\Mail\MapPointContact\EditLink;
use App\Models\Group;
use App\Models\MapPoint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Mail::fake();

    $this->point = MapPoint::factory()->for(Group::factory()->create(['name' => 'Solar-Initiative Darmstadt']))->create([
        'title' => 'Garten mit Balkonkraftwerk',
        'published' => true,
        'contact_email' => 'owner@example.org',
        'contact_email_verified_at' => now(),
        'contact_enabled' => true,
    ]);
});

function editToken(): string
{
    $mail = Mail::queued(EditLink::class)->last();

    return basename((string) parse_url($mail->manageUrl, PHP_URL_PATH));
}

/**
 * @return TestResponse<Response>
 */
function requestEditLink(TestCase $test, MapPoint $point, string $ip = '10.0.0.1'): TestResponse
{
    return $test->withServerVariables(['REMOTE_ADDR' => $ip])
        ->post(route('map-contact.unsubscribe.request', $point));
}

test('the request page answers the same way for points with and without a contact address', function (): void {
    $withoutContact = MapPoint::factory()->create(['contact_email' => null]);

    $responses = [];

    foreach ([$this->point, $withoutContact] as $point) {
        $redirect = requestEditLink($this, $point);
        $page = $this->get((string) $redirect->headers->get('Location'));

        $responses[] = [$redirect->status(), $page->status(), $page->viewData('page')['props']['linkRequested']];
    }

    expect($responses[0])->toBe($responses[1])
        ->and($responses[0])->toBe([302, 200, true]);

    Mail::assertQueued(EditLink::class, 1);
    Mail::assertQueued(EditLink::class, fn ($mail) => $mail->hasTo('owner@example.org'));
});

test('the edit link mail names the initiative and is stored without content', function (): void {
    requestEditLink($this, $this->point);

    Mail::queued(EditLink::class)->sole()->assertSeeInHtml('Solar-Initiative Darmstadt');
});

test('contact requests can be switched off and on again with the token', function (): void {
    requestEditLink($this, $this->point);
    $token = editToken();

    $this->get(route('map-contact.manage.show', $token))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('MapPointContact/Manage')->where('contactEnabled', true))
        ->assertDontSee('owner@example.org');

    $this->post(route('map-contact.manage.disable', $token))
        ->assertInertia(fn ($page) => $page->where('contactEnabled', false));
    expect($this->point->fresh()->contact_enabled)->toBeFalse()
        ->and($this->point->fresh()->hasContact())->toBeFalse();

    $this->post(route('map-contact.manage.enable', $token))
        ->assertInertia(fn ($page) => $page->where('contactEnabled', true));
    expect($this->point->fresh()->contact_enabled)->toBeTrue();
});

test('a new link invalidates the old one', function (): void {
    requestEditLink($this, $this->point);
    $oldToken = editToken();

    requestEditLink($this, $this->point);
    $newToken = editToken();

    expect($newToken)->not->toBe($oldToken);

    $this->post(route('map-contact.manage.disable', $oldToken))
        ->assertInertia(fn ($page) => $page->where('token', null));
    expect($this->point->fresh()->contact_enabled)->toBeTrue();

    $this->post(route('map-contact.manage.disable', $newToken));
    expect($this->point->fresh()->contact_enabled)->toBeFalse();
});

test('an edit link expires after seven days', function (): void {
    requestEditLink($this, $this->point);
    $token = editToken();

    $this->travel(8)->days();

    $this->post(route('map-contact.manage.disable', $token))
        ->assertInertia(fn ($page) => $page->where('token', null));
    expect($this->point->fresh()->contact_enabled)->toBeTrue();
});

test('requesting edit links is rate limited per IP and point without changing the answer', function (): void {
    foreach (range(1, 4) as $i) {
        requestEditLink($this, $this->point)->assertRedirect(route('map-contact.unsubscribe', $this->point));
    }

    Mail::assertQueued(EditLink::class, 3);

    requestEditLink($this, $this->point, '10.0.0.2');
    Mail::assertQueued(EditLink::class, 4);
});

test('the unsubscribe page has no token and does not reveal the address', function (): void {
    $this->get(route('map-contact.unsubscribe', $this->point))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('MapPointContact/Unsubscribe')->where('linkRequested', false))
        ->assertDontSee('owner@example.org');
});
