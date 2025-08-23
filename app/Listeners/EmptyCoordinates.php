<?php

namespace App\Listeners;

use App\Events\AdviceCreated;
use App\Events\AdviceUpdated;

class EmptyCoordinates
{
    public function handle(AdviceUpdated|AdviceCreated $event)
    {
        $advice = $event->advice;
        if ($advice->wasChanged(['street', 'city', 'zip', 'street_number'])) {
            $advice = $advice->fresh();
            $advice->lat = null;
            $advice->lng = null;
            $advice->save();
        }
    }
}
