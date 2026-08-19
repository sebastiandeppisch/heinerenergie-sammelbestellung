<?php

use App\Enums\AdviceStatusResult;
use App\Enums\Aggregation;
use App\Events\Advice\StatusChangedEvent;
use App\Models\Advice;
use App\Models\AdviceStatus;
use App\Models\Group;
use App\Services\AdviceStatusDistributionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

// ── helpers ────────────────────────────────────────────────────────────────────

function newAdvice(Group $group, ?AdviceStatus $status, Carbon $createdAt): Advice
{
    return Advice::withoutEvents(fn () => Advice::factory()->create([
        'group_id' => $group->id,
        'advice_status_id' => $status?->id,
        'created_at' => $createdAt,
    ]));
}

function addStatusChange(Advice $advice, ?AdviceStatus $from, AdviceStatus $to, Carbon $at): void
{
    $event = new StatusChangedEvent($advice, null, $from?->id, $to->id);
    $record = $advice->events()->create(['user_id' => null, 'event' => $event]);
    DB::table('advice_events')->where('id', $record->id)->update(['created_at' => $at]);
}

function kpiService(): AdviceStatusDistributionService
{
    return app(AdviceStatusDistributionService::class);
}

// ── computeForDate ─────────────────────────────────────────────────────────────

it('counts advice with no events using current status', function (): void {
    $group = Group::factory()->create();
    $status = AdviceStatus::factory()->create([
        'name' => 'In Arbeit',
        'result' => AdviceStatusResult::InProgress,
        'group_id' => $group->id,
    ]);

    newAdvice($group, $status, now()->subMonth());

    $result = kpiService()->computeForDate($group, now());

    expect($result->statusCounts['In Bearbeitung'])->toBe(1)
        ->and($result->statusCounts['Neu'])->toBe(0);
});

it('returns New result when advice has no status set', function (): void {
    $group = Group::factory()->create();
    newAdvice($group, null, now()->subMonth());

    $result = kpiService()->computeForDate($group, now());

    expect($result->statusCounts['Neu'])->toBe(1);
});

it('walks back events after cutoff to find status at cutoff date', function (): void {
    $group = Group::factory()->create();
    $initial = AdviceStatus::factory()->create(['name' => 'Offen', 'result' => AdviceStatusResult::New, 'group_id' => $group->id]);
    $later = AdviceStatus::factory()->create(['name' => 'Fertig', 'result' => AdviceStatusResult::Completed, 'group_id' => $group->id]);

    $advice = newAdvice($group, $initial, now()->subMonths(4));
    addStatusChange($advice, $initial, $later, now()->subMonth()); // after cutoff
    $advice->updateQuietly(['advice_status_id' => $later->id]);
    $advice->load('status');

    $result = kpiService()->computeForDate($group, now()->subMonths(2));

    expect($result->statusCounts['Neu'])->toBe(1)
        ->and($result->statusCounts['Erfolgreich beraten'])->toBe(0);
});

it('uses toStatus of event before cutoff', function (): void {
    $group = Group::factory()->create();
    $initial = AdviceStatus::factory()->create(['name' => 'Offen', 'result' => AdviceStatusResult::New, 'group_id' => $group->id]);
    $after = AdviceStatus::factory()->create(['name' => 'Fertig', 'result' => AdviceStatusResult::Completed, 'group_id' => $group->id]);

    $advice = newAdvice($group, $initial, now()->subMonths(4));
    addStatusChange($advice, $initial, $after, now()->subMonths(3)); // before cutoff
    $advice->updateQuietly(['advice_status_id' => $after->id]);
    $advice->load('status');

    $result = kpiService()->computeForDate($group, now()->subMonths(2));

    expect($result->statusCounts['Erfolgreich beraten'])->toBe(1)
        ->and($result->statusCounts['Neu'])->toBe(0);
});

it('treats null fromStatus as New when walking back', function (): void {
    $group = Group::factory()->create();
    $nextStatus = AdviceStatus::factory()->create(['name' => 'Fertig', 'result' => AdviceStatusResult::Completed, 'group_id' => $group->id]);

    $advice = newAdvice($group, null, now()->subMonths(3));
    addStatusChange($advice, null, $nextStatus, now()->subMonth()); // after cutoff
    $advice->updateQuietly(['advice_status_id' => $nextStatus->id]);
    $advice->load('status');

    $result = kpiService()->computeForDate($group, now()->subMonths(2));

    expect($result->statusCounts['Neu'])->toBe(1);
});

it('excludes advices created after the cutoff date', function (): void {
    $group = Group::factory()->create();
    $status = AdviceStatus::factory()->create(['result' => AdviceStatusResult::InProgress, 'group_id' => $group->id]);

    newAdvice($group, $status, now()->addDay());

    $result = kpiService()->computeForDate($group, now());

    expect(array_sum($result->statusCounts))->toBe(0);
});

it('only counts advices belonging to the given group', function (): void {
    $groupA = Group::factory()->create();
    $groupB = Group::factory()->create();
    $status = AdviceStatus::factory()->create(['result' => AdviceStatusResult::Completed, 'group_id' => $groupA->id]);

    newAdvice($groupA, $status, now()->subMonth());
    newAdvice($groupB, $status, now()->subMonth());

    $result = kpiService()->computeForDate($groupA, now());

    expect($result->statusCounts['Erfolgreich beraten'])->toBe(1);
});

it('formats the date as Y-m-d', function (): void {
    $group = Group::factory()->create();

    $result = kpiService()->computeForDate($group, Carbon::parse('2024-03-15'));

    expect($result->date)->toBe('2024-03-15');
});

it('returns zero counts for all buckets when no advices exist', function (): void {
    $group = Group::factory()->create();

    $result = kpiService()->computeForDate($group, now());

    expect($result->statusCounts)->toBe([
        'Neu' => 0,
        'In Bearbeitung' => 0,
        'Erfolgreich beraten' => 0,
        'Nicht erfolgreich' => 0,
    ]);
});

// ── generateCutoffDates ────────────────────────────────────────────────────────

it('generates monthly cutoff dates as end-of-month, last clamped to to-date', function (): void {
    $from = Carbon::parse('2024-01-01');
    $to = Carbon::parse('2024-03-15');

    $dates = kpiService()->generateCutoffDates($from, $to, Aggregation::Month);

    expect($dates)->toHaveCount(3)
        ->and($dates[0]->format('Y-m-d'))->toBe('2024-01-31')
        ->and($dates[1]->format('Y-m-d'))->toBe('2024-02-29')
        ->and($dates[2]->format('Y-m-d'))->toBe('2024-03-15');
});

it('generates weekly cutoff dates as end-of-week (Sunday)', function (): void {
    $from = Carbon::parse('2024-01-01'); // Monday
    $to = Carbon::parse('2024-01-14');

    $dates = kpiService()->generateCutoffDates($from, $to, Aggregation::Week);

    expect($dates)->toHaveCount(2)
        ->and($dates[0]->format('Y-m-d'))->toBe('2024-01-07')
        ->and($dates[1]->format('Y-m-d'))->toBe('2024-01-14');
});

it('generates daily cutoff dates', function (): void {
    $from = Carbon::parse('2024-01-01');
    $to = Carbon::parse('2024-01-03');

    $dates = kpiService()->generateCutoffDates($from, $to, Aggregation::Day);

    expect($dates)->toHaveCount(3)
        ->and($dates[0]->format('Y-m-d'))->toBe('2024-01-01')
        ->and($dates[1]->format('Y-m-d'))->toBe('2024-01-02')
        ->and($dates[2]->format('Y-m-d'))->toBe('2024-01-03');
});

// ── getDistribution ────────────────────────────────────────────────────────────

it('returns one data point per cutoff date', function (): void {
    $group = Group::factory()->create();
    $from = now()->subMonths(2)->startOfMonth();
    $to = now();

    $results = kpiService()->getDistribution($group, $from, $to, Aggregation::Month);

    expect($results)->toHaveCount(3)
        ->each->toHaveKeys(['date', 'statusCounts']);
});

it('caches past cutoff dates but not the current period', function (): void {
    $cache = Cache::spy();

    $group = Group::factory()->create();
    $from = now()->subMonths(2)->startOfMonth();
    $to = now();

    kpiService()->getDistribution($group, $from, $to, Aggregation::Month);

    $cache->shouldHaveReceived('rememberForever')->atLeast()->once();
});
