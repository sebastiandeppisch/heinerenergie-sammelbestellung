<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\AdviceCreated;
use App\Jobs\AssignAdviceToGroupByAddress;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class InitiateAdviceAssignment implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(AdviceCreated $event): void
    {
        // Etwas warten, damit CalculateCoordinates zuerst ausgeführt werden kann
        AssignAdviceToGroupByAddress::dispatch($event->advice)
            ->delay(now()->addSeconds(30));
    }
}
