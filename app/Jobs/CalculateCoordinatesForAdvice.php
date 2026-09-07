<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\FetchCoordinateByAddress;
use App\Enums\GeocodingStatus;
use App\Models\Advice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Resolves the coordinates of an advice.
 *
 * Runs on the deferred connection, so it happens after the response has been
 * sent. A failing geocoder therefore never becomes an error for whoever
 * submitted the form.
 */
class CalculateCoordinatesForAdvice implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public Advice $advice)
    {
        // The deferred connection runs a job exactly once, right after the
        // response has been sent. Retrying is the job of the
        // advices:geocode-pending command.
        $this->onConnection('deferred');
    }

    public function handle(): void
    {
        $advice = $this->advice->fresh();

        // Anything other than pending means the advice is gone, somebody placed
        // the pin by hand, or the address is already known to be unresolvable.
        if ($advice === null || $advice->geocoding_status?->isOpen() !== true) {
            return;
        }

        $address = $advice->address;

        if ($address === null) {
            $advice->geocoding_status = GeocodingStatus::NOT_FOUND;
            $advice->saveQuietly();

            return;
        }

        $coordinate = app(FetchCoordinateByAddress::class)($address);

        if ($coordinate === null) {
            $advice->geocoding_status = GeocodingStatus::NOT_FOUND;
        } else {
            $advice->coordinate = $coordinate;
            $advice->geocoding_status = GeocodingStatus::SUCCESS;
        }

        // Quietly, otherwise the updated event dispatches this job again and a
        // not_found result would loop forever, because it leaves lat and lng
        // empty.
        $advice->saveQuietly();
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Geocoding an advice failed', [
            'advice_id' => $this->advice->id,
            'exception' => $exception,
        ]);

        $advice = $this->advice->fresh();

        if ($advice === null) {
            return;
        }

        $advice->geocoding_status = GeocodingStatus::FAILED;
        $advice->saveQuietly();
    }
}
