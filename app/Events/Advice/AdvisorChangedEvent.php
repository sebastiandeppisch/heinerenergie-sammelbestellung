<?php

namespace App\Events\Advice;

use App\Models\Advice;
use App\Models\User;

class AdvisorChangedEvent extends AdviceEvent
{
    public function __construct(
        public Advice $advice,
        public ?User $user,
        public ?User $fromAdvisor,
        public ?User $toAdvisor,
        public ?string $reason = null
    ) {
        parent::__construct($advice, $user);
    }

    public function getDescription(): string
    {
        if ($this->fromAdvisor === null) {
            return 'Beratung an '.$this->toAdvisor->name.' zugewiesen';
        }

        if ($this->toAdvisor === null) {
            return 'Beratung von '.$this->fromAdvisor->name.' freigegeben';
        }

        return 'Beratung von '.$this->fromAdvisor->name.' zu '.$this->toAdvisor->name.' übertragen';
    }
}
