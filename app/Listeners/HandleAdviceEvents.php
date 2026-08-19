<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\Advice\AdvisorChangedEvent;
use App\Events\Advice\PersonDataChangedEvent;
use App\Events\Advice\StatusChangedEvent;
use App\Events\AdviceSaving;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class HandleAdviceEvents
{
    private const array PERSON_FIELDS = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'street',
        'street_number',
        'zip',
        'city',
    ];

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

        $personChanges = [];
        foreach (self::PERSON_FIELDS as $field) {
            if ($advice->isDirty($field)) {
                $personChanges[$field] = [
                    'from' => $advice->getOriginal($field),
                    'to' => $advice->getAttribute($field),
                ];
            }
        }

        if ($personChanges !== []) {
            event(new PersonDataChangedEvent(
                $advice,
                Auth::user(),
                $personChanges
            ));
        }
    }
}
