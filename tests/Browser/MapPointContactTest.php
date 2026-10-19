<?php

use App\Mail\MapPointContact\ContactRequestConfirmation;
use App\Mail\MapPointContact\ContactRequestForward;
use App\Models\Group;
use App\Models\MapEmbed;
use App\Models\MapPoint;
use App\Models\MapPointCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

test('a visitor contacts a point from the map popup and the owner gets the message after confirmation', function (): void {
    Mail::fake();

    $group = Group::factory()->create();
    $category = MapPointCategory::factory()->for($group)->withoutImage()->create();
    $point = MapPoint::factory()->for($group)->create([
        'title' => 'Garten mit Balkonkraftwerk',
        'published' => true,
        'category_id' => $category->id,
        'contact_email' => 'owner@example.org',
        'contact_email_verified_at' => now(),
        'contact_enabled' => true,
    ]);
    $mapEmbed = MapEmbed::factory()->for($group)->create(['lat' => $point->coordinate->lat, 'lng' => $point->coordinate->lng, 'zoom' => 14]);
    $mapEmbed->mapPointCategories()->sync([$category->id]);

    $page = visit(route('map.public', $mapEmbed))
        ->assertNoJavaScriptErrors()
        ->click('.leaflet-marker-icon')
        ->assertSee('Garten mit Balkonkraftwerk')
        ->click('Kontakt aufnehmen')
        ->assertSee('Kontakt aufnehmen')
        ->fill('#contact-name', 'Erika Muster')
        ->fill('#contact-email', 'erika@example.org')
        ->fill('#contact-message', 'Darf ich mir deinen Garten anschauen?')
        ->click('Nachricht senden')
        ->assertSee('Bitte bestätige deine E-Mail-Adresse')
        ->assertNoJavaScriptErrors();

    Mail::assertNotQueued(ContactRequestForward::class);

    $confirmationUrl = Mail::queued(ContactRequestConfirmation::class)->sole()->confirmationUrl;

    $page->navigate($confirmationUrl)
        ->assertSee('Kontaktanfrage bestätigen')
        ->click('button:has-text("Kontaktanfrage bestätigen")')
        ->assertSee('Danke, deine Nachricht ist unterwegs')
        ->assertNoJavaScriptErrors();

    Mail::assertQueued(ContactRequestForward::class, fn ($mail): bool => $mail->hasTo('owner@example.org') && $mail->hasReplyTo('erika@example.org'));
});

test('the table tab offers contact only for points that accept it', function (): void {
    $group = Group::factory()->create();
    $category = MapPointCategory::factory()->for($group)->withoutImage()->create();
    $contactable = MapPoint::factory()->for($group)->create([
        'title' => 'Mit Kontakt',
        'published' => true,
        'category_id' => $category->id,
        'contact_email' => 'owner@example.org',
        'contact_email_verified_at' => now(),
        'contact_enabled' => true,
    ]);
    MapPoint::factory()->for($group)->create(['title' => 'Ohne Kontakt', 'published' => true, 'category_id' => $category->id]);
    $mapEmbed = MapEmbed::factory()->for($group)->create(['show_table' => true]);
    $mapEmbed->mapPointCategories()->sync([$category->id]);

    $page = visit(route('map.public', $mapEmbed))
        ->click('Tabelle')
        ->assertSee('Mit Kontakt')
        ->assertSee('Ohne Kontakt')
        ->assertNoJavaScriptErrors();

    expect($page->script('document.querySelectorAll("table a[href*=\'/contact\']").length'))->toBe(1);

    $page->click('table >> text=Kontakt aufnehmen')
        ->assertPathIs("/map/{$mapEmbed->uuid}/points/{$contactable->uuid}/contact")
        ->assertSee('Nachricht senden');
});
