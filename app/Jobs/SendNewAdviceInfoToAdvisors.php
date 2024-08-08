<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\Advice;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Collection;
use App\Notifications\NewAdviceNearby;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class SendNewAdviceInfoToAdvisors implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Advice $advice)
    {
        //
    }

    public function handle()
    {
        if ($this->advice->coordinate === null) {
            $this->release(60);
            return;
        }

        foreach($this->advisors() as $advisor) {
            if($advisor->shouldBeNotifiedForNearbyAdvice($this->advice)) {
                $distance = $this->advice->getDistanceToUser($advisor);
                $advisor->notify(new NewAdviceNearby($this->advice, $distance));
            }
        }
    }

    /**
     * @return Collection<User>
     */
    private function advisors(): Collection{
        return User::whereNotNull('advice_radius')->get();
    }
}
