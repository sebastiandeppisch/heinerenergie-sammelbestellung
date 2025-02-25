<?php

namespace App\Events\Advice;

use App\Models\Advice;
use App\Models\User;

class AdviceSharedAdvisorRemoved extends AdviceEvent
{
    public function __construct(
        public Advice $advice,
        public ?User $user,
        public User $sharedAdvisor
    ) {
        parent::__construct($advice, $user);
    }

    public function getDescription(): string
    {
        return "Beratung nicht mehr mit {$this->sharedAdvisor->name} geteilt";
    }
}
