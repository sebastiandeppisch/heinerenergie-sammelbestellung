<?php

declare(strict_types=1);

use App\Actions\FetchCoordinateByAddress;
use App\Actions\FetchCoordinateByFreeText;
use App\Enums\GeocodingStatus;
use App\Exceptions\NominatimUnavailableException;
use App\Jobs\PrefetchPostalCodePolygons;
use App\Models\Advice;
use App\Models\User;
use App\ValueObjects\Coordinate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
});

it('resolves an address to coordinates', function (): void {
    $response = $this->actingAs($this->user)->postJson(route('api.geocode.address'), [
        'street' => 'Luisenplatz',
        'street_number' => '1',
        'zip' => '64283',
        'city' => 'Darmstadt',
    ]);

    $response->assertOk();
    $response->assertJsonStructure(['coordinate' => ['lat', 'lng']]);
});

it('reports an unknown address as an empty coordinate rather than an error', function (): void {
    app()->bind(FetchCoordinateByAddress::class, fn (): Closure => fn (): ?Coordinate => null);

    $response = $this->actingAs($this->user)->postJson(route('api.geocode.address'), [
        'street' => 'Nirgendwo',
        'street_number' => '99',
        'zip' => '00000',
        'city' => 'Niemandsland',
    ]);

    $response->assertOk();
    $response->assertJson(['coordinate' => null]);
});

it('answers with 503 when the geocoder is unavailable', function (): void {
    app()->bind(FetchCoordinateByAddress::class, fn (): Closure => function (): never {
        throw NominatimUnavailableException::requestFailed(new RuntimeException('connection refused'));
    });

    $response = $this->actingAs($this->user)->postJson(route('api.geocode.address'), [
        'street' => 'Luisenplatz',
        'street_number' => '1',
        'zip' => '64283',
        'city' => 'Darmstadt',
    ]);

    $response->assertStatus(503);
    $response->assertJsonStructure(['message']);
});

it('does not expose the geocoding endpoint to guests', function (): void {
    $this->postJson(route('api.geocode.address'), ['zip' => '64283'])->assertStatus(401);
});

it('answers the map search with 503 when the geocoder is unavailable', function (): void {
    app()->bind(FetchCoordinateByFreeText::class, fn (): Closure => function (): never {
        throw NominatimUnavailableException::requestFailed(new RuntimeException('connection refused'));
    });

    $this->actingAs($this->user)
        ->getJson(route('api.map.search', ['query' => 'Darmstadt']))
        ->assertStatus(503);
});

it('queues a postal code prefetch without waiting for it', function (): void {
    Queue::fake();

    $response = $this->actingAs($this->user)
        ->postJson(route('api.postal-code-area.prefetch'), ['postal_code' => '64283']);

    $response->assertStatus(202);

    Queue::assertPushed(
        PrefetchPostalCodePolygons::class,
        fn (PrefetchPostalCodePolygons $job): bool => $job->postalCode === '64283'
    );
});

it('rejects a postal code that is not five digits', function (): void {
    $this->actingAs($this->user)
        ->postJson(route('api.postal-code-area.prefetch'), ['postal_code' => '123'])
        ->assertStatus(422);
});

it('stores a pin placed by hand and stops further geocoding', function (): void {
    $advice = Advice::factory()->withoutCoordinates()->create([
        'advisor_id' => $this->user->id,
        'geocoding_status' => GeocodingStatus::NOT_FOUND,
    ]);

    $response = $this->actingAs($this->user)
        ->putJson(route('api.advices.coordinate', $advice), ['lat' => 49.87, 'lng' => 8.65]);

    $response->assertOk();

    $advice->refresh();

    expect($advice->lat)->toBe(49.87)
        ->and($advice->lng)->toBe(8.65)
        ->and($advice->geocoding_status)->toBe(GeocodingStatus::MANUAL);
});
