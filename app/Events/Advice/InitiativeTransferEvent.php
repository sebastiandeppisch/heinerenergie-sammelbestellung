<?php

declare(strict_types=1);

namespace App\Events\Advice;

use App\Models\Advice;
use App\Models\Group;
use App\Models\User;

class InitiativeTransferEvent extends AdviceEvent
{
    public function __construct(
        public Advice $advice,
        public ?User $user,
        public ?Group $fromGroup,
        public Group $toGroup,
        public ?string $reason = null
    ) {
        parent::__construct($advice, $user);
    }

    public function getDescription(): string
    {
        $base = 'Beratung wurde '.
            ($this->fromGroup ? "von {$this->fromGroup->name} " : '').
            "zu {$this->toGroup->name} übertragen";

        if ($this->reason) {
            $base .= " (Grund: {$this->reason})";
        }

        return $base;
    }
}
