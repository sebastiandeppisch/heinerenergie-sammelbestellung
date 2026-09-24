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
use App\Models\User;
use App\Notifications\NewAdviceNearby;
use App\ValueObjects\Coordinate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\RawMessage;
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

it('queues the geocoding instead of running it inside the request', function (): void {
    Queue::fake();

    submitAdviceForm(FormDefinitionToAdvice::factory()->create())->assertSuccessful();

    Queue::assertPushed(CalculateCoordinatesForAdvice::class);
});

it('still notifies nearby advisors even though the lookup moved behind the response', function (): void {
    $this->withDefer();

    $advisor = User::factory()->create([
        'lat' => 49.8728475,
        'lng' => 8.6510204,
        'advice_radius' => 50_000,
    ]);

    Notification::fake();

    submitAdviceForm(FormDefinitionToAdvice::factory()->create())->assertSuccessful();

    // The notification job runs before the geocoding job, so it has to resolve
    // the position itself. Releasing would be dropped without a worker.
    Notification::assertSentTo($advisor, NewAdviceNearby::class);
});

it('keeps the form submission when the mail server is down', function (): void {
    $this->withDefer();

    // A real SMTP failure happens in the transport, which the queued mailable
    // only reaches after the response. On the old sync connection that was
    // still inside the request and took the whole submission down with it.
    Mail::extend('failing', fn (): TransportInterface => new class implements TransportInterface
    {
        public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
        {
            throw new RuntimeException('SMTP connect() failed');
        }

        public function __toString(): string
        {
            return 'failing';
        }
    });

    config(['mail.default' => 'failing', 'mail.mailers.failing' => ['transport' => 'failing']]);

    $response = submitAdviceForm(FormDefinitionToAdvice::factory()->create());

    $response->assertSuccessful();
    $response->assertSessionHasNoErrors();

    expect(Advice::count())->toBe(1);
});

it('defaults to the deferred connection, so an instance without a worker still works', function (): void {
    // phpunit.xml deliberately does not set QUEUE_CONNECTION, so this really is
    // the fallback in config/queue.php that a shared hosting install gets.
    // Falling back to sync would put the geocoder and the mail server back into
    // the request.
    expect($_SERVER['QUEUE_CONNECTION'] ?? null)->toBeNull()
        ->and(config('queue.default'))->toBe('deferred');

    // The job must not pin a connection, otherwise an instance that does run a
    // worker could not opt into the durable queue via QUEUE_CONNECTION.
    expect(new CalculateCoordinatesForAdvice(Advice::factory()->create())->connection)->toBeNull();
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
