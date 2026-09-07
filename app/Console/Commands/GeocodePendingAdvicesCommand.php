<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\GeocodingStatus;
use App\Exceptions\NominatimUnavailableException;
use App\Jobs\CalculateCoordinatesForAdvice;
use App\Models\Advice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Safety net for advices whose geocoding never completed.
 *
 * The geocoding job runs on the deferred connection, which fires exactly once
 * and only when the response was successful. Anything lost to a 5xx, a fatal
 * error, or an outage of OpenStreetMap is picked up here.
 */
class GeocodePendingAdvicesCommand extends Command
{
    protected $signature = 'advices:geocode-pending
        {--limit=50 : How many advices to process in this run}
        {--include-unknown : Also process legacy advices that have no status yet}';

    protected $description = 'Retries the coordinate lookup for advices that are still pending or failed';

    public function handle(): int
    {
        $statuses = [GeocodingStatus::PENDING, GeocodingStatus::FAILED];

        $advices = Advice::query()
            ->whereNull('lat')
            ->whereNull('lng')
            ->where(function ($query) use ($statuses): void {
                $query->whereIn('geocoding_status', $statuses);

                if ($this->option('include-unknown')) {
                    $query->orWhereNull('geocoding_status');
                }
            })
            ->oldest('updated_at')
            ->limit((int) $this->option('limit'))
            ->get();

        if ($advices->isEmpty()) {
            $this->info('Nothing to geocode.');

            return self::SUCCESS;
        }

        $this->info("Geocoding {$advices->count()} advices.");

        foreach ($advices as $advice) {
            $advice->geocoding_status = GeocodingStatus::PENDING;
            $advice->saveQuietly();

            try {
                CalculateCoordinatesForAdvice::dispatchSync($advice);
            } catch (NominatimUnavailableException $e) {
                // The service is down. Retrying the rest of the batch would
                // only hammer it, so stop and leave them pending.
                $this->markFailed($advice, $e);
                $this->error('OpenStreetMap is unavailable, stopping this run.');

                return self::FAILURE;
            }
        }

        $this->info('Done.');

        return self::SUCCESS;
    }

    private function markFailed(Advice $advice, NominatimUnavailableException $exception): void
    {
        Log::error('Catching up on an advice geocoding failed', [
            'advice_id' => $advice->id,
            'exception' => $exception,
        ]);

        $advice->geocoding_status = GeocodingStatus::FAILED;
        $advice->saveQuietly();
    }
}
