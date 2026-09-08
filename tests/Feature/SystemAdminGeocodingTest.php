<?php

declare(strict_types=1);

use App\Actions\FetchCoordinateByAddress;
use App\Enums\GeocodingStatus;
use App\Models\Advice;
use App\Models\User;
use App\Services\NominatimThrottle;
use App\Services\SessionService;
use App\ValueObjects\Coordinate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Sleep;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // The throttle really waits, so a test must never sleep for real.
    Sleep::fake();
    $this->admin = User::factory()->create(['is_admin' => true]);
    app(SessionService::class)->actAsSystemAdmin();
});

it('shows the throttle interval and how many advices are waiting', function (): void {
    Advice::factory()->withoutCoordinates()->create(['geocoding_status' => GeocodingStatus::FAILED]);
    Advice::factory()->withoutCoordinates()->create(['geocoding_status' => GeocodingStatus::NOT_FOUND]);

    $this->actingAs($this->admin)
        ->get(route('system-admin'))
        ->assertOk()
        // JSON turns the 2.0 second interval into a plain 2.
        ->assertInertia(fn ($page) => $page
            ->where('geocoding.interval', 2)
            ->where('geocoding.queueConnection', 'deferred')
            ->where('geocoding.counts.failed', 1)
            ->where('geocoding.counts.not_found', 1)
        );
});

it('shows the current wait so the operator can see that throttling is happening', function (): void {
    // Two reservations put the next free slot one interval into the future.
    app(NominatimThrottle::class)->await();
    app(NominatimThrottle::class)->await();

    $this->actingAs($this->admin)
        ->get(route('system-admin'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('geocoding.maxWait', 10)
            ->where('geocoding.currentWait', fn (float $wait): bool => $wait > 0)
        );
});

it('reports no wait while nothing is queued behind the rate limit', function (): void {
    $this->actingAs($this->admin)
        ->get(route('system-admin'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('geocoding.currentWait', 0));
});

it('lets an operator run the catch up without shell access', function (): void {
    $advice = Advice::factory()->withoutCoordinates()->create([
        'geocoding_status' => GeocodingStatus::FAILED,
    ]);

    app()->bind(FetchCoordinateByAddress::class, fn (): Closure => fn (): Coordinate => new Coordinate(49.87, 8.65));

    $this->actingAs($this->admin)
        ->post(route('system-admin.geocode-pending'))
        ->assertRedirect(route('system-admin'));

    expect($advice->fresh()->geocoding_status)->toBe(GeocodingStatus::SUCCESS);
});

it('does not let a normal user run the catch up', function (): void {
    app(SessionService::class)->clear();

    $advice = Advice::factory()->withoutCoordinates()->create([
        'geocoding_status' => GeocodingStatus::FAILED,
    ]);

    // The project renders authorization failures as a redirect rather than a
    // bare 403, so the proof is that nothing happened.
    $this->actingAs(User::factory()->create())
        ->post(route('system-admin.geocode-pending'))
        ->assertRedirect();

    expect($advice->fresh()->geocoding_status)->toBe(GeocodingStatus::FAILED);
});
