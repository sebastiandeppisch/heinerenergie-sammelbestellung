<?php

namespace App\Events\Advice;

use App\Models\Advice;
use App\Models\AdviceStatus;
use App\Models\User;

class StatusChangedEvent extends AdviceEvent
{
    public function __construct(
        Advice $advice,
        ?User $user,
        public ?string $fromStatus,
        public ?string $toStatus
    ) {
        parent::__construct($advice, $user);
        $this->fromStatus = $fromStatus ? AdviceStatus::find($fromStatus)->name : 'Kein Status';
        $this->toStatus = $toStatus ? AdviceStatus::find($toStatus)->name : 'Kein Status';
    }

    public function getDescription(): string
    {
        return "Status wurde von '{$this->fromStatus}' zu '{$this->toStatus}' geändert";
    }

    public function __serialize(): array
    {
        return [
            'fromStatus' => $this->fromStatus,
            'toStatus' => $this->toStatus,
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->fromStatus = $data['fromStatus'];
        $this->toStatus = $data['toStatus'];
    }
}
