<?php

namespace App\Events\Advice;

use App\Models\Advice;
use App\Models\AdviceStatus;
use App\Models\User;

class StatusChangedEvent extends AdviceEvent
{
    public ?string $fromStatus;

    public ?string $toStatus;

    public function __construct(
        Advice $advice,
        ?User $user,
        ?int $fromStatusId,
        ?int $toStatusId
    ) {
        parent::__construct($advice, $user);
        $this->fromStatus = AdviceStatus::find($fromStatusId)?->name;
        $this->toStatus = AdviceStatus::find($toStatusId)?->name;
    }

    public function getDescription(): string
    {
        if (! $this->fromStatus) {
            return "Status wurde zu '{$this->toStatus}' geändert";
        }

        if (! $this->toStatus) {
            return "Status wurde von '{$this->fromStatus}' zu 'Kein Status' geändert";
        }

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
