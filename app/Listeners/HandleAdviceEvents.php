<?php

namespace App\Listeners;

use App\Events\Advice\InitiativeTransferEvent;
use App\Events\Advice\StatusChangedEvent;
use App\Events\AdviceSaving;
use App\Models\Group;
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

        if ($advice->isDirty('group_id')) {
            $oldGroup = $advice->getOriginal('group_id') ? Group::find($advice->getOriginal('group_id')) : null;
            $newGroup = Group::find($advice->group_id);

            event(new InitiativeTransferEvent(
                $advice,
                Auth::user(),
                $oldGroup,
                $newGroup
            ));
        }
    }
}
