<?php

namespace Tests\Feature;

use App\Events\Advice\InitiativeTransferEvent;
use App\Events\Advice\StatusChangedEvent;
use App\Models\Advice;
use App\Models\AdviceStatus;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();

    // Create required statuses
    $this->status1 = AdviceStatus::create(['id' => 1, 'name' => 'Initial']);
    $this->status2 = AdviceStatus::create(['id' => 2, 'name' => 'In Progress']);
    $this->status3 = AdviceStatus::create(['id' => 3, 'name' => 'Completed']);

    $this->group = Group::factory()->create(['name' => 'Test Initiative']);

    $this->advice = Advice::factory()->create([
        'advice_status_id' => $this->status1->id,
        'group_id' => $this->group,
    ]);
});

test('it creates an event when status changes', function () {
    $this->actingAs($this->user);

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

    $newGroup = Group::factory()->create(['name' => 'New Initiative']);

    $this->advice->group()->associate($newGroup);
    $this->advice->save();

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

    $newGroup = Group::factory()->create(['name' => 'New Initiative']);
    $this->advice->group()->associate($newGroup);
    $this->advice->save();

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
