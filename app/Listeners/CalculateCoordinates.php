<?php

namespace App\Listeners;

use App\Events\AdviceCreated;
use App\Events\AdviceUpdated;
use Illuminate\Queue\InteractsWithQueue;
use App\Jobs\CalculateCoordinatesForAdvice;
use Illuminate\Contracts\Queue\ShouldQueue;

class CalculateCoordinates
{
    public function handle(AdviceUpdated|AdviceCreated $event)
    {
        $advice = $event->advice;
        if($advice->lat === null || $advice->long === null) {
            CalculateCoordinatesForAdvice::dispatch($advice);
        }
    }
}
