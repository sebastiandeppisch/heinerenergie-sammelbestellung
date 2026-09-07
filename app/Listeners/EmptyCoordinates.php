<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\GeocodingStatus;
use App\Events\AdviceCreated;
use App\Events\AdviceUpdated;
use App\Jobs\CalculateCoordinatesForAdvice;

/**
 * Drops the coordinates when the address changed and starts a fresh lookup.
 */
class EmptyCoordinates
{
    public function handle(AdviceUpdated|AdviceCreated $event): void
    {
        if (! $event->advice->wasChanged(['street', 'city', 'zip', 'street_number'])) {
            return;
        }

        $advice = $event->advice->fresh();

        if ($advice === null) {
            return;
        }

        $advice->lat = null;
        $advice->lng = null;
        // A new address deserves a new attempt, even when the previous one was
        // not found, failed, or had its pin placed by hand.
        $advice->geocoding_status = GeocodingStatus::PENDING;

        // Quietly, so this does not fire another updated event just to have the
        // very same listeners run a second time.
        $advice->saveQuietly();

        CalculateCoordinatesForAdvice::dispatch($advice);
    }
}
