<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Events\Advice\InitiativeTransferEvent;
use App\Events\Advice\PersonDataChangedEvent;
use App\Events\Advice\StatusChangedEvent;
use App\Models\Advice;
use App\Models\Group;
use App\Models\User;
use App\Services\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\post;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();

    // Create required statuses

    $this->group = Group::factory()->create(['name' => 'Test Initiative']);

    $this->group->users()->attach($this->user, ['is_admin' => true]);

    $this->status1 = $this->group->ownStatuses()->create(['name' => 'Initial']);
    $this->status2 = $this->group->ownStatuses()->create(['name' => 'In Progress']);
    $this->status3 = $this->group->ownStatuses()->create(['name' => 'Completed']);

    $this->advice = Advice::factory()->create([
        'advice_status_id' => $this->status1->id,
        'group_id' => $this->group,
    ]);

    app(SessionService::class)->actWithoutSelectingGroup();
    config()->set('app.group_context', 'global');
    $this->actingAs($this->user);
});

test('it creates an event when a single person field changes', function (): void {
    $oldEmail = $this->advice->email;
    $this->advice->email = 'new@example.com';
    $this->advice->save();

    $event = $this->advice->events()->latest()->first();

    expect($event)
        ->not()->toBeNull()
        ->and($event->event)->toBeInstanceOf(PersonDataChangedEvent::class)
        ->and($event->description)->toBe(
            "Persönliche Daten geändert:\nE-Mail von '{$oldEmail}' zu 'new@example.com' geändert"
        );
});

test('it batches multiple person field changes into one event', function (): void {
    $this->advice->first_name = 'Neuer';
    $this->advice->last_name = 'Name';
    $this->advice->save();

    $personEvents = $this->advice->events()->get()->filter(
        fn ($e): bool => $e->event instanceof PersonDataChangedEvent
    );

    expect($personEvents)->toHaveCount(1);
    expect($personEvents->first()->event)->toBeInstanceOf(PersonDataChangedEvent::class)
        ->changes->toHaveKeys(['first_name', 'last_name']);
});

test('it records old and new values for each changed person field', function (): void {
    $oldFirstName = $this->advice->first_name;
    $this->advice->first_name = 'Geändert';
    $this->advice->save();

    $event = $this->advice->events()->latest()->first()->event;

    expect($event)->toBeInstanceOf(PersonDataChangedEvent::class)
        ->changes->toMatchArray(['first_name' => ['from' => $oldFirstName, 'to' => 'Geändert']]);
});

test('it creates no person data event when only non-person fields change', function (): void {
    $this->advice->commentary = 'Neuer Kommentar';
    $this->advice->save();

    $personEvents = $this->advice->events()->get()->filter(
        fn ($e): bool => $e->event instanceof PersonDataChangedEvent
    );

    expect($personEvents)->toHaveCount(0);
});

test('it stores the user who changed person data', function (): void {
    $this->advice->phone = '0123456789';
    $this->advice->save();

    $event = $this->advice->events()->latest()->first();

    expect($event->user_id)->toBe($this->user->id);
});

test('person data event description covers all tracked fields', function (): void {
    // Only fields that are actually dirty end up in the event's changes, so the
    // advice needs a known starting value for every tracked field. With the
    // factory's random values a field could be generated with exactly the value
    // set below and would then be missing: fake()->buildingNumber() returns "1"
    // in roughly 2% of all runs, which made this test fail intermittently.
    $fields = [
        'first_name' => ['old' => 'Vorher-Vorname', 'new' => 'Max'],
        'last_name' => ['old' => 'Vorher-Nachname', 'new' => 'Mustermann'],
        'email' => ['old' => 'vorher@example.com', 'new' => 'max@example.com'],
        'phone' => ['old' => '0300000001', 'new' => '0300000000'],
        'street' => ['old' => 'Vorherstraße', 'new' => 'Hauptstraße'],
        'street_number' => ['old' => '99', 'new' => '1'],
        'zip' => ['old' => '54321', 'new' => '12345'],
        'city' => ['old' => 'Vorherstadt', 'new' => 'Berlin'],
    ];

    $this->advice = Advice::factory()->create([
        'advice_status_id' => $this->status1->id,
        'group_id' => $this->group->id,
        ...array_map(fn (array $values): string => $values['old'], $fields),
    ]);

    foreach ($fields as $field => $values) {
        $this->advice->$field = $values['new'];
    }
    $this->advice->save();

    $event = $this->advice->events()->latest()->first()->event;

    expect($event)->toBeInstanceOf(PersonDataChangedEvent::class)
        ->changes->toHaveKeys(array_keys($fields));
});

test('it creates an event when status changes', function (): void {
    $this->advice->advice_status_id = $this->status2->id;
    $this->advice->save();

    $this->advice = $this->advice->refresh();

    $event = $this->advice->events()->latest()->first();

    expect($event)
        ->not()->toBeNull()
        ->and($event->event)
        ->toBeInstanceOf(StatusChangedEvent::class)
        ->and($event->description)->toBe("Status wurde von '{$this->status1->name}' zu '{$this->status2->name}' geändert");
});

test('it creates an event when group is transferred', function (): void {
    $this->actingAs($this->user);

    $newGroup = Group::factory()->create(['name' => 'New Initiative', 'accepts_transfers' => true]);

    transferAdvice($this->advice, $newGroup);

    $event = $this->advice->events()->latest()->first();

    expect($event)
        ->not()->toBeNull()
        ->and($event->event)
        ->toBeInstanceOf(InitiativeTransferEvent::class)
        ->and($event->description)->toBe('Beratung wurde von Test Initiative zu New Initiative übertragen');
});

test('it includes reason in transfer description when provided', function (): void {
    $this->actingAs($this->user);

    $newGroup = Group::factory()->create(['name' => 'New Initiative']);
    $reason = 'Außerhalb des Beratungsgebiets';

    event(new InitiativeTransferEvent(
        $this->advice,
        $this->user,
        $this->group,
        $newGroup,
        $reason
    ));

    $event = $this->advice->events()->latest()->first();

    expect($event)
        ->not()->toBeNull()
        ->and($event->description)->toBe("Beratung wurde von Test Initiative zu New Initiative übertragen (Grund: {$reason})");
});

test('events can be retrieved in chronological order', function (): void {
    $this->actingAs($this->user);

    // Create multiple events
    $this->advice->advice_status_id = $this->status2->id;
    $this->advice->save();

    $newGroup = Group::factory()->create(['name' => 'New Initiative', 'accepts_transfers' => true]);
    transferAdvice($this->advice, $newGroup);

    $events = $this->advice->events()->orderBy('created_at')->get();

    expect($events)
        ->toHaveCount(2)
        ->sequence(
            fn ($event) => $event->description->toContain('Status wurde von'),
            fn ($event) => $event->description->toContain('Beratung wurde von')
        );
});

test('events retain user who triggered them', function (): void {
    $this->actingAs($this->user);

    $this->advice->advice_status_id = $this->status3->id;
    $this->advice->save();

    $event = $this->advice->events()->latest()->first();

    expect($event)
        ->not()->toBeNull()
        ->and($event->user_id)->toBe($this->user->id);
});

function transferAdvice(Advice $advice, Group $newGroup, ?string $reason = null): void
{
    $response = post(route('advices.transfer', $advice), [
        'group_id' => $newGroup->uuid,
        'reason' => $reason,
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('advices.show', $advice));
}
