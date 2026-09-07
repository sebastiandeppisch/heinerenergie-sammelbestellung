<?php

declare(strict_types=1);

use App\Exceptions\NominatimUnavailableException;
use App\Services\NominatimThrottle;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Sleep;

beforeEach(function (): void {
    Cache::flush();
    // Without this the suite would really sleep two seconds per request.
    Sleep::fake();
    Carbon::setTestNow('2026-09-07 12:00:00');
});

afterEach(function (): void {
    Sleep::fake(false);
    Carbon::setTestNow();
});

it('lets the first request through without waiting', function (): void {
    new NominatimThrottle(interval: 2.0, maxWait: 10.0)->await();

    Sleep::assertSleptTimes(0);
});

it('spaces consecutive requests by the configured interval', function (): void {
    $throttle = new NominatimThrottle(interval: 2.0, maxWait: 10.0);

    $throttle->await();
    $throttle->await();
    $throttle->await();

    // Time is frozen, so the second caller waits one interval and the third two.
    Sleep::assertSequence([
        Sleep::for(2)->seconds(),
        Sleep::for(4)->seconds(),
    ]);
});

it('does not make a caller wait once the interval has already passed', function (): void {
    $throttle = new NominatimThrottle(interval: 2.0, maxWait: 10.0);

    $throttle->await();

    Carbon::setTestNow(Carbon::now()->addSeconds(5));

    $throttle->await();

    Sleep::assertSleptTimes(0);
});

it('refuses instead of queueing up beyond the maximum wait', function (): void {
    $throttle = new NominatimThrottle(interval: 2.0, maxWait: 3.0);

    // Two slots are free within the three second budget, the third is not.
    $throttle->await();
    $throttle->await();

    expect(fn () => $throttle->await())->toThrow(NominatimUnavailableException::class);
});

it('keeps the reserved slots of separate throttle instances in one shared cache', function (): void {
    new NominatimThrottle(interval: 2.0, maxWait: 10.0)->await();
    new NominatimThrottle(interval: 2.0, maxWait: 10.0)->await();

    // The second instance must see the slot the first one booked, otherwise
    // separate web requests would all fire at OpenStreetMap at once.
    Sleep::assertSequence([
        Sleep::for(2)->seconds(),
    ]);
});
