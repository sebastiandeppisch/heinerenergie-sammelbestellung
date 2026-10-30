<?php

use App\Models\MapPointContactRequest;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the prune command keeps confirmed requests for 90 and unconfirmed ones for 7 days by default', function (): void {
    $oldConfirmed = MapPointContactRequest::factory()->confirmed()->create(['created_at' => now()->subDays(91)]);
    $recentConfirmed = MapPointContactRequest::factory()->confirmed()->create(['created_at' => now()->subDays(89)]);
    $oldUnconfirmed = MapPointContactRequest::factory()->create(['created_at' => now()->subDays(8)]);
    $recentUnconfirmed = MapPointContactRequest::factory()->create(['created_at' => now()->subDays(6)]);

    expect(config('map_contact.retention.confirmed_days'))->toBe(90)
        ->and(config('map_contact.retention.unconfirmed_days'))->toBe(7);

    $this->artisan('map-contact:prune')->assertSuccessful();

    expect(MapPointContactRequest::pluck('id')->sort()->values()->all())
        ->toBe([$recentConfirmed->id, $recentUnconfirmed->id]);
    expect(MapPointContactRequest::find($oldConfirmed->id))->toBeNull()
        ->and(MapPointContactRequest::find($oldUnconfirmed->id))->toBeNull();
});

test('the retention periods can be configured', function (): void {
    config(['map_contact.retention.confirmed_days' => 10]);

    MapPointContactRequest::factory()->confirmed()->create(['created_at' => now()->subDays(11)]);

    $this->artisan('map-contact:prune')->assertSuccessful();

    expect(MapPointContactRequest::count())->toBe(0);
});

test('the prune command is scheduled daily', function (): void {
    $event = collect(app(Schedule::class)->events())
        ->first(fn ($event): bool => str_contains((string) $event->command, 'map-contact:prune'));

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('0 0 * * *');
});
