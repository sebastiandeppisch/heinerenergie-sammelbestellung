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

    public function __construct(public Advice $advice, private AdviceService $adviceService)
    {
        //
    }

    public function handle()
    {
        Log::info('Sending new advice info to advisors', [
            'advice_id' => $this->advice->id,
            'advisors_count' => $this->advisors()->count(),
        ]);
        if ($this->advice->coordinate === null) {
            $this->release(60);

            return;
        }

        foreach ($this->advisors() as $advisor) {
            if ($advisor->shouldBeNotifiedForNearbyAdvice($this->advice)) {
                $distance = $this->adviceService->getDistance($this->advice, $advisor);
                $advisor->notify(new NewAdviceNearby($this->advice, $distance));
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
