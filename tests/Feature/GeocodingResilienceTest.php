<?php

declare(strict_types=1);

use App\Actions\FetchCoordinateByAddress;
use App\Enums\AdviceType;
use App\Enums\GeocodingStatus;
use App\Events\AdviceUpdated;
use App\Exceptions\NominatimUnavailableException;
use App\Jobs\CalculateCoordinatesForAdvice;
use App\Models\Advice;
use App\Models\FormDefinitionToAdvice;
use App\ValueObjects\Coordinate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\AutoAttachesFormEmbedToken;

uses(RefreshDatabase::class, AutoAttachesFormEmbedToken::class);

/**
 * Makes the geocoding action behave as if OpenStreetMap were unreachable.
 */
function failGeocoding(): void
{
    app()->bind(FetchCoordinateByAddress::class, fn (): Closure => function (): never {
        throw NominatimUnavailableException::requestFailed(new RuntimeException('connection refused'));
    });
}

/**
 * Makes the geocoding action behave as if the address were unknown.
 */
function geocodingFindsNothing(): void
{
    app()->bind(FetchCoordinateByAddress::class, fn (): Closure => fn (): ?Coordinate => null);
}

/**
 * @return TestResponse<Response>
 */
function submitAdviceForm(FormDefinitionToAdvice $config): TestResponse
{
    return test()->post(route('form.submit', $config->formDefinition), [
        $config->firstNameField->uuid => fake()->firstName(),
        $config->lastNameField->uuid => fake()->lastName(),
        $config->emailField->uuid => fake()->safeEmail(),
        $config->addressField->uuid => [
            'street' => fake()->streetAddress(),
            'street_number' => fake()->buildingNumber(),
            'city' => fake()->city(),
            'zip' => fake()->postcode(),
        ],
        $config->phoneField->uuid => fake()->phoneNumber(),
        $config->adviceTypeField->uuid => AdviceType::Virtual->value,
    ]);
}

it('keeps the form submission when the geocoder is unavailable', function (): void {
    // Real deferring, because that is the whole point here: the job runs after
    // the response, where the callback collection swallows and reports the
    // exception instead of turning it into a 500 for the visitor.
    $this->withDefer();

    failGeocoding();

    $response = submitAdviceForm(FormDefinitionToAdvice::factory()->create());

    $response->assertSuccessful();
    $response->assertSessionHasNoErrors();

    expect(Advice::count())->toBe(1)
        ->and(Advice::first()->geocoding_status)->toBe(GeocodingStatus::FAILED);
});

it('marks an advice as not found when the geocoder knows no such address', function (): void {
    geocodingFindsNothing();

    submitAdviceForm(FormDefinitionToAdvice::factory()->create())->assertSuccessful();

    expect(Advice::first()->geocoding_status)->toBe(GeocodingStatus::NOT_FOUND);
});

it('queues the geocoding on the deferred connection so it runs after the response', function (): void {
    Queue::fake();

    submitAdviceForm(FormDefinitionToAdvice::factory()->create())->assertSuccessful();

    // assertPushedOn checks the queue name, the connection has to be asserted
    // on the job itself.
    Queue::assertPushed(
        CalculateCoordinatesForAdvice::class,
        fn (CalculateCoordinatesForAdvice $job): bool => $job->connection === 'deferred'
    );
});

it('does not dispatch itself again after a not found result', function (): void {
    geocodingFindsNothing();

    $advice = Advice::factory()->withoutCoordinates()->create([
        'geocoding_status' => GeocodingStatus::PENDING,
    ]);

    Event::fake([AdviceUpdated::class]);

    new CalculateCoordinatesForAdvice($advice)->handle();

    // Writing not_found leaves lat and lng empty, so an updated event here
    // would send the listener straight back into dispatching this job.
    Event::assertNotDispatched(AdviceUpdated::class);

    expect($advice->fresh()->geocoding_status)->toBe(GeocodingStatus::NOT_FOUND);
});

it('catches up on an advice whose lookup failed earlier', function (): void {
    $advice = Advice::factory()->withoutCoordinates()->create([
        'geocoding_status' => GeocodingStatus::FAILED,
    ]);

    app()->bind(FetchCoordinateByAddress::class, fn (): Closure => fn (): Coordinate => new Coordinate(49.87, 8.65));

    $this->artisan('advices:geocode-pending')->assertSuccessful();

    expect($advice->fresh()->geocoding_status)->toBe(GeocodingStatus::SUCCESS)
        ->and($advice->fresh()->lat)->toBe(49.87);
});

it('leaves an advice with an unknown address alone when catching up', function (): void {
    $advice = Advice::factory()->withoutCoordinates()->create([
        'geocoding_status' => GeocodingStatus::NOT_FOUND,
    ]);

    app()->bind(FetchCoordinateByAddress::class, fn (): Closure => function (): never {
        throw new RuntimeException('The catch up must not ask again for a known miss');
    });

    $this->artisan('advices:geocode-pending')->assertSuccessful();

    expect($advice->fresh()->geocoding_status)->toBe(GeocodingStatus::NOT_FOUND);
});

it('never overwrites a pin that was placed by hand', function (): void {
    $advice = Advice::factory()->create([
        'lat' => 50.0,
        'lng' => 8.0,
        'geocoding_status' => GeocodingStatus::MANUAL,
    ]);

    app()->bind(FetchCoordinateByAddress::class, fn (): Closure => fn (): Coordinate => new Coordinate(1.0, 2.0));

    new CalculateCoordinatesForAdvice($advice)->handle();

    expect($advice->fresh()->lat)->toBe(50.0)
        ->and($advice->fresh()->geocoding_status)->toBe(GeocodingStatus::MANUAL);
});
