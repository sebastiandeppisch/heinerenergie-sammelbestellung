<?php

namespace App\Listeners;

use App\Events\Advice\AdvisorChangedEvent;
use App\Events\Advice\StatusChangedEvent;
use App\Events\AdviceSaving;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class HandleAdviceEvents
{
    public function handle(AdviceSaving $event): void
    {
        $advice = $event->advice;

        if ($advice->isDirty('advice_status_id')) {

            event(new StatusChangedEvent(
                $advice,
                Auth::user(),
                $advice->getOriginal('advice_status_id'),
                $advice->advice_status_id
            ));
        }

        if ($advice->isDirty('advisor_id')) {

            $oldAdvisor = $advice->getOriginal('advisor_id') ? User::find($advice->getOriginal('advisor_id')) : null;
            $newAdvisor = $advice->advisor_id ? User::find($advice->advisor_id) : null;

            event(new AdvisorChangedEvent(
                $advice,
                Auth::user(),
                $oldAdvisor,
                $newAdvisor
            ));
        }
    }
}
