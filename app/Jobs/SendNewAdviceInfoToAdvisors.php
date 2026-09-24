<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Exceptions\NominatimUnavailableException;
use App\Models\Advice;
use App\Models\User;
use App\Notifications\NewAdviceNearby;
use App\Services\AdviceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class SendNewAdviceInfoToAdvisors implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private AdviceService $adviceService;

    public int $tries = 2;

    public function __construct(public Advice $advice)
    {
        $this->adviceService = app(AdviceService::class);
    }

    public function handle(): void
    {
        Log::info('Sending new advice info to advisors', [
            'advice_id' => $this->advice->id,
            'advisors_count' => $this->advisors()->count(),
        ]);
        if ($this->advice->coordinate === null) {
            // The lookup runs in its own job and may not have finished yet.
            // Releasing would be silently dropped on a connection without a
            // worker, so resolve the position here instead. The lookup is
            // cached and guards itself against running twice.
            try {
                CalculateCoordinatesForAdvice::dispatchSync($this->advice);
            } catch (NominatimUnavailableException $e) {
                Log::warning('Cannot notify advisors, the geocoder is unavailable', [
                    'advice_id' => $this->advice->id,
                    'exception' => $e,
                ]);

                return;
            }

            $this->advice = $this->advice->fresh() ?? $this->advice;
        }

        if ($this->advice->coordinate === null) {
            Log::info('Advice has no position, advisors cannot be selected by distance', [
                'advice_id' => $this->advice->id,
            ]);

            return;
        }

        Log::info('advice coordinates are set, send mails', [
            'advice_id' => $this->advice->id,
        ]);

        foreach ($this->advisors() as $advisor) {
            if ($advisor->shouldBeNotifiedForNearbyAdvice($this->advice)) {
                Log::info('Notifying advisor about new nearby advice', [
                    'advice_id' => $this->advice->id,
                    'advisor_id' => $advisor->id,
                ]);
                $distance = $this->adviceService->getDistance($this->advice, $advisor);
                Log::info('send notification');
                $advisor->notify(new NewAdviceNearby($this->advice, $distance));
                Log::info('notification send');
            } else {
                Log::info('Advisor is not nearby, not notifying', [
                    'advice_id' => $this->advice->id,
                    'advisor_id' => $advisor->id,
                ]);
            }
        }
    }

    /**
     * @return Collection<int, User>
     */
    private function advisors(): Collection
    {
        return User::whereNotNull('advice_radius')->get();
    }
}
