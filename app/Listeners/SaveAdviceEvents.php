<?php

namespace App\Listeners;

use App\Events\Advice\AdviceEvent;

class SaveAdviceEvents
{
    public function handle(AdviceEvent $event): void
    {
        if ($event->advice->id === null) {
            return;
        }

        $advice = $event->advice;
        $user = $event->user;

        $advice->events()->create([
            'user_id' => $user?->id,
            'event' => $event,
        ]);
    }
}
