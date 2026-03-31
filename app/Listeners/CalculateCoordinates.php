<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\AdviceCreated;
use App\Events\AdviceUpdated;
use App\Jobs\CalculateCoordinatesForAdvice;

class CalculateCoordinates
{
    public function handle(AdviceUpdated|AdviceCreated $event): void
    {
        $advice = $event->advice;
        if ($advice->lng === null || $advice->lat === null) {
            CalculateCoordinatesForAdvice::dispatch($advice);
        }
    }
}
