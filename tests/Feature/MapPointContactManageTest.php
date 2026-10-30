<?php

use App\Mail\MapPointEditLinks;
use App\Models\Group;
use App\Models\MapPoint;
use App\Services\MapPointEditLinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;

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

test('the request page answers the same way for points with and without a contact address', function (): void {
    $withoutContact = MapPoint::factory()->create(['contact_email' => null]);

    foreach ([$this->point, $withoutContact] as $point) {
        $this->post(route('map-point.manage.send-for-point', $point))
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page->component('MapPointManage/Message')->where('status', 'sent'));
    }

    Mail::assertQueued(MapPointEditLinks::class, 1);
    Mail::assertQueued(MapPointEditLinks::class, fn (MapPointEditLinks $mail): bool => $mail->hasTo('owner@example.org'));
});

test('the edit link mail names the initiative', function (): void {
    $this->post(route('map-point.manage.send-for-point', $this->point));

    Mail::queued(MapPointEditLinks::class)->sole()->assertSeeInHtml('Solar-Initiative Darmstadt');
});

test('contact requests can be switched off and on again on the manage page', function (): void {
    $token = app(MapPointEditLinkService::class)->issue($this->point);

    $this->get(route('map-point.manage.show', $token))
        ->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page->component('MapPointManage/Manage')->where('mapPoint.contact_enabled', true))
        ->assertDontSee('owner@example.org');

    $this->put(route('map-point.manage.contact', $token), ['enabled' => false])->assertRedirect(route('map-point.manage.show', $token));
    expect($this->point->fresh()->contact_enabled)->toBeFalse()
        ->and($this->point->fresh()->hasContact())->toBeFalse();

    $this->put(route('map-point.manage.contact', $token), ['enabled' => true]);
    expect($this->point->fresh()->contact_enabled)->toBeTrue()
        ->and($this->point->fresh()->hasContact())->toBeTrue();
});

test('a new link invalidates the old one for contact requests too', function (): void {
    $editLinks = app(MapPointEditLinkService::class);
    $oldToken = $editLinks->issue($this->point);
    $newToken = $editLinks->issue($this->point);

    $this->put(route('map-point.manage.contact', $oldToken), ['enabled' => false])
        ->assertInertia(fn (Assert $page): Assert => $page->component('MapPointManage/Message')->where('status', 'invalid'));
    expect($this->point->fresh()->contact_enabled)->toBeTrue();

    $this->put(route('map-point.manage.contact', $newToken), ['enabled' => false]);
    expect($this->point->fresh()->contact_enabled)->toBeFalse();
});

test('links of older mails lead to the manage page of the point', function (): void {
    $token = app(MapPointEditLinkService::class)->issue($this->point);

    $this->get(route('map-contact.unsubscribe', $this->point))
        ->assertRedirect(route('map-point.manage.request-for-point', $this->point));
    $this->get(route('map-contact.manage.show', $token))
        ->assertRedirect(route('map-point.manage.show', $token));
});

test('the request page has no token and does not reveal the address', function (): void {
    $this->get(route('map-point.manage.request-for-point', $this->point))
        ->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page->component('MapPointManage/Request')->where('mapPointId', $this->point->uuid))
        ->assertDontSee('owner@example.org');
});
