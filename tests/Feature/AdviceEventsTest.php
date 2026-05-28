<?php

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

beforeEach(function () {
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

test('it creates an event when a single person field changes', function () {
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

test('it batches multiple person field changes into one event', function () {
    $this->advice->first_name = 'Neuer';
    $this->advice->last_name = 'Name';
    $this->advice->save();

    $personEvents = $this->advice->events()->get()->filter(
        fn ($e) => $e->event instanceof PersonDataChangedEvent
    );

    expect($personEvents)->toHaveCount(1)
        ->and($personEvents->first()->event->changes)->toHaveKeys(['first_name', 'last_name']);
});

test('it records old and new values for each changed person field', function () {
    $oldFirstName = $this->advice->first_name;
    $this->advice->first_name = 'Geändert';
    $this->advice->save();

    $event = $this->advice->events()->latest()->first()->event;

    expect($event)->toBeInstanceOf(PersonDataChangedEvent::class)
        ->and($event->changes['first_name']['from'])->toBe($oldFirstName)
        ->and($event->changes['first_name']['to'])->toBe('Geändert');
});

test('it creates no person data event when only non-person fields change', function () {
    $this->advice->commentary = 'Neuer Kommentar';
    $this->advice->save();

    $personEvents = $this->advice->events()->get()->filter(
        fn ($e) => $e->event instanceof PersonDataChangedEvent
    );

    expect($personEvents)->toHaveCount(0);
});

test('it stores the user who changed person data', function () {
    $this->advice->phone = '0123456789';
    $this->advice->save();

    $event = $this->advice->events()->latest()->first();

    expect($event->user_id)->toBe($this->user->id);
});

test('person data event description covers all tracked fields', function () {
    $fields = [
        'first_name' => ['old' => $this->advice->first_name, 'new' => 'Max'],
        'last_name' => ['old' => $this->advice->last_name, 'new' => 'Mustermann'],
        'email' => ['old' => $this->advice->email, 'new' => 'max@example.com'],
        'phone' => ['old' => $this->advice->phone, 'new' => '0300000000'],
        'street' => ['old' => $this->advice->street, 'new' => 'Hauptstraße'],
        'street_number' => ['old' => $this->advice->street_number, 'new' => '1'],
        'zip' => ['old' => $this->advice->zip, 'new' => '12345'],
        'city' => ['old' => $this->advice->city, 'new' => 'Berlin'],
    ];

    foreach ($fields as $field => $values) {
        $this->advice->$field = $values['new'];
    }
    $this->advice->save();

    $event = $this->advice->events()->latest()->first()->event;

    expect($event)->toBeInstanceOf(PersonDataChangedEvent::class)
        ->and($event->changes)->toHaveKeys(array_keys($fields));
});

test('it creates an event when status changes', function () {
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

test('it creates an event when group is transferred', function () {
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

test('it includes reason in transfer description when provided', function () {
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

test('events can be retrieved in chronological order', function () {
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

test('events retain user who triggered them', function () {
    $this->actingAs($this->user);

    $this->advice->advice_status_id = $this->status3->id;
    $this->advice->save();

    $event = $this->advice->events()->latest()->first();

    expect($event)
        ->not()->toBeNull()
        ->and($event->user_id)->toBe($this->user->id);
});

function transferAdvice(Advice $advice, Group $newGroup, ?string $reason = null)
{
    $response = post(route('advices.transfer', $advice), [
        'group_id' => $newGroup->uuid,
        'reason' => $reason,
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('advices.show', $advice));
}
