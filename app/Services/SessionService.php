<?php

namespace App\Services;

use App\Models\Group;

class SessionService
{
    public function getCurrentGroup(): ?Group
    {
        $groupId = session()->get('actAsGroupId');

        return $groupId ? Group::findOrFail($groupId) : null;
    }

    public function actAsGroup(Group $group, bool $asAdmin = false): void
    {
        $this->clear();

        session()->put('actAsGroupId', $group->id);
        session()->put('actAsGroupAdmin', $asAdmin);
    }

    public function actAsSystemAdmin(): void
    {
        $this->clear();

        session()->put('actAsSystemAdmin', true);
    }

    public function actsAsGroupAdmin(): bool
    {
        return session()->get('actAsGroupAdmin', false);
    }

    public function actsAsSystemAdmin(): bool
    {
        return session()->get('actAsSystemAdmin', false);
    }

    public function clear(): void
    {
        session()->forget(['actAsGroupId', 'actAsGroupAdmin', 'actAsSystemAdmin']);
    }
}
