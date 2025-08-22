<?php

namespace App\Services;

use App\Models\Group;

class CurrentGroupService
{
    private ?Group $group = null;

    public function setGroup(Group $group): void
    {
        $this->group = $group;
    }

    public function getGroup(): ?Group
    {
        $sessionService = app(SessionService::class);

        return $this->group ?? $sessionService->getCurrentGroup();
    }
}
