<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\GeocodingStatus;
use App\Events\AdviceCreated;
use App\Events\AdviceUpdated;
use App\Jobs\CalculateCoordinatesForAdvice;

class CalculateCoordinates
{
    public function handle(AdviceUpdated|AdviceCreated $event): void
    {
        $advice = $event->advice;

        if ($advice->lat !== null && $advice->lng !== null) {
            return;
        }

        // not_found, failed and manual are terminal. They only start over when
        // the address changes, which EmptyCoordinates resets to pending.
        if ($advice->geocoding_status !== null && ! $advice->geocoding_status->isOpen()) {
            return;
        }

        if ($advice->geocoding_status === null) {
            $advice->geocoding_status = GeocodingStatus::PENDING;
            $advice->saveQuietly();
        }

        CalculateCoordinatesForAdvice::dispatch($advice);
    }
}
