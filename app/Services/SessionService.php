<?php

namespace App\Services;

use App\Models\Group;

class SessionService
{
    public function isGroupMissing(): bool
    {
        $currentGroup = $this->getCurrentGroup();

        if ($this->actsAsSystemAdmin()) {
            return false;
        }

        if (session()->has('actWithoutSelectingGroup')) {
            return false;
        }

        return ! $currentGroup;
    }

    public function actWithoutSelectingGroup(): void
    {
        session()->put('actWithoutSelectingGroup', true);
    }

    public function getCurrentGroup(): ?Group
    {
        $groupId = session()->get('actAsGroupId');

        return $groupId ? Group::findOrFail($groupId) : null;
    }

    public function actAsGroup(Group $group, bool $asAdmin = false): void
    {
        $this->clear();
        session([
            'actAsGroupId' => $group->id,
            'actAsGroupAdmin' => $asAdmin,
            'actAsSystemAdmin' => null,
            'actWithoutSelectingGroup' => false,
        ]);
    }

    public function actAsSystemAdmin(): void
    {
        $this->clear();

        session([
            'actAsSystemAdmin' => true,
            'actAsGroupId' => null,
            'actAsGroupAdmin' => null,
            'actWithoutSelectingGroup' => false,
        ]);
    }

    public function getRedirectAfterSelectionAndForget(): ?string
    {
        $redirectTo = session()->get('redirectAfterSelection');

        session()->forget('redirectAfterSelection');

        return $redirectTo;
    }

    public function setRedirectAfterSelection(string $url): void
    {
        session()->put('redirectAfterSelection', $url);
    }

    public function actsAsGroupAdmin(): bool
    {
        return session('actAsGroupAdmin', false) === true;
    }

    public function actsAsSystemAdmin(): bool
    {
        return session('actAsSystemAdmin', false) === true;
    }

    public function clear(): void
    {
        session()->forget([
            'actAsGroupId',
            'actAsGroupAdmin',
            'actAsSystemAdmin',
            'actWithoutSelectingGroup',
            'redirectAfterSelection',
        ]);
    }
}
