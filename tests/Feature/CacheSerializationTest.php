<?php

declare(strict_types=1);

use App\Data\StatusDistributionPointData;
use App\Enums\Aggregation;
use App\Services\AdviceStatusDistributionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

afterEach(function (): void {
    // Only the throwaway stores this file created. Deleting the whole testing
    // directory would take the tracked .gitignore with it.
    foreach (File::glob(storage_path('framework/testing/cache-*')) as $directory) {
        File::deleteDirectory($directory);
    }
});

it('reads a cached StatusDistributionPointData back as a StatusDistributionPointData', function (): void {
    useFileCacheStore();

    Cache::forever('test.distribution', new StatusDistributionPointData('2026-01-01', ['open' => 3]));

    expect(Cache::get('test.distribution'))->toBeInstanceOf(StatusDistributionPointData::class);
});

it('returns StatusDistributionPointData objects on a cache hit', function (): void {
    useFileCacheStore();

    $service = new AdviceStatusDistributionService;
    $from = Carbon::parse('2026-01-01');
    $to = Carbon::parse('2026-01-03');

    $service->getDistribution(null, $from, $to, Aggregation::Day);
    $cached = $service->getDistribution(null, $from, $to, Aggregation::Day);

    expect($cached)->each->toBeInstanceOf(StatusDistributionPointData::class);
});
