<?php

use App\Enums\AdviceStatusResult;
use App\Models\Advice;
use App\Models\AdviceStatus;
use App\Models\Group;
use App\Models\User;
use App\Services\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->group = Group::factory()->create();
    app(SessionService::class)->actAsGroup($this->group);
});

it('requires authentication', function (): void {
    $this->getJson(route('api.kpi.status-distribution', ['from' => '2024-01-01', 'to' => '2024-03-31', 'aggregation' => 'month']))
        ->assertUnauthorized();
});

it('validates required parameters', function (): void {
    $this->actingAs($this->user)
        ->getJson(route('api.kpi.status-distribution'))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['from', 'aggregation']);
});

it('validates aggregation enum values', function (): void {
    $this->actingAs($this->user)
        ->getJson(route('api.kpi.status-distribution', ['from' => '2024-01-01', 'aggregation' => 'invalid']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['aggregation']);
});

it('validates to must be after or equal to from', function (): void {
    $this->actingAs($this->user)
        ->getJson(route('api.kpi.status-distribution', ['from' => '2024-06-01', 'to' => '2024-01-01', 'aggregation' => 'month']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['to']);
});

it('returns json array of data points', function (): void {
    $this->actingAs($this->user)
        ->getJson(route('api.kpi.status-distribution', [
            'from' => now()->subMonths(2)->startOfMonth()->format('Y-m-d'),
            'to' => now()->format('Y-m-d'),
            'aggregation' => 'month',
        ]))
        ->assertSuccessful()
        ->assertJsonIsArray()
        ->assertJsonCount(3)
        ->assertJsonStructure([['date', 'statusCounts']]);
});

it('returns status counts keyed by label', function (): void {
    $response = $this->actingAs($this->user)
        ->getJson(route('api.kpi.status-distribution', [
            'from' => now()->subMonth()->startOfMonth()->format('Y-m-d'),
            'to' => now()->format('Y-m-d'),
            'aggregation' => 'month',
        ]))
        ->assertSuccessful()
        ->json();

    expect($response[0]['statusCounts'])->toHaveKeys([
        'Neu',
        'In Bearbeitung',
        'Erfolgreich beraten',
        'Nicht erfolgreich',
    ]);
});

it('counts advices in the current group only', function (): void {
    $otherGroup = Group::factory()->create();
    $status = AdviceStatus::factory()->create(['result' => AdviceStatusResult::Completed, 'group_id' => $this->group->id]);

    Advice::withoutEvents(fn () => Advice::factory()->create([
        'group_id' => $this->group->id,
        'advice_status_id' => $status->id,
    ]));
    Advice::withoutEvents(fn () => Advice::factory()->create([
        'group_id' => $otherGroup->id,
        'advice_status_id' => $status->id,
    ]));

    $response = $this->actingAs($this->user)
        ->getJson(route('api.kpi.status-distribution', [
            'from' => now()->startOfMonth()->format('Y-m-d'),
            'to' => now()->format('Y-m-d'),
            'aggregation' => 'month',
        ]))
        ->assertSuccessful()
        ->json();

    $lastPoint = last($response);
    expect($lastPoint['statusCounts']['Erfolgreich beraten'])->toBe(1);
});
