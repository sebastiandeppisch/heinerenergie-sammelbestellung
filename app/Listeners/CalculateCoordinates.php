<?php

namespace App\Listeners;

use App\Events\AdviceCreated;
use App\Events\AdviceUpdated;
use App\Jobs\CalculateCoordinatesForAdvice;

class CalculateCoordinates
{
    public function handle(AdviceUpdated|AdviceCreated $event)
    {
        $advice = $event->advice;
        if ($advice->lat === null || $advice->long === null) {
            CalculateCoordinatesForAdvice::dispatch($advice);
        }
    }
}
