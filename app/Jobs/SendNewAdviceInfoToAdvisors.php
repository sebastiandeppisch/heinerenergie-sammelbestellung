<?php

namespace App\Jobs;

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
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private AdviceService $adviceService;

    public $tries = 2;

    public function __construct(public Advice $advice)
    {
        $this->adviceService = app(AdviceService::class);
    }

    public function handle()
    {
        Log::info('Sending new advice info to advisors', [
            'advice_id' => $this->advice->id,
            'advisors_count' => $this->advisors()->count(),
        ]);
        if ($this->advice->coordinate === null) {
            $this->release(60);
            Log::info('Advice coordinate is null, releasing job', [
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
     * @return Collection<User>
     */
    private function advisors(): Collection
    {
        return User::whereNotNull('advice_radius')->get();
    }
}
