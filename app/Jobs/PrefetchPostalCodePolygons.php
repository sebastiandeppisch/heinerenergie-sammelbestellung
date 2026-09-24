<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\FetchPolygonsByPostalCode;
use App\Exceptions\NominatimUnavailableException;
use App\Exceptions\PostalCodeAreaException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Warms the polygon cache for a single postal code.
 *
 * Building a consulting area needs one Nominatim request per postal code, and
 * those are two seconds apart. Fetching each one while the user is still typing
 * keeps the actual "load area" click fast.
 */
class PrefetchPostalCodePolygons implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public string $postalCode) {}

    public function handle(): void
    {
        try {
            app(FetchPolygonsByPostalCode::class)($this->postalCode);
        } catch (PostalCodeAreaException|NominatimUnavailableException $e) {
            // Warming the cache is best effort. The user gets a real error
            // message when they actually build the area.
            Log::info('Prefetching a postal code area failed', [
                'postalCode' => $this->postalCode,
                'exception' => $e,
            ]);
        }
    }
}
