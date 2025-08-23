<?php

namespace App\Context;

use App\Models\Group;
use App\Models\User;
use InvalidArgumentException;

class GroupContext implements GroupContextContract
{
    use AncestorHelper;

    public function __construct(
        private ?Group $currentGroup,
        private bool $isActingAsSystemAdmin,
        private ?User $user,
        private bool $isActingAsGroupAdmin = false
    ) {

        if ($this->currentGroup !== null) {
            $this->currentGroup->loadMissing('parent');
            $this->currentGroup->loadMissing('children');
            foreach ($this->currentGroup->children as $child) {
                $child->loadMissing('parent');
            }
            $group = $this->currentGroup;
            while ($group->parent_id) {
                $group = $group->parent;
                $group->loadMissing('parent');
            }
        }

    }

    private function assertUserMatches(User $user)
    {
        if ($this->user === null || $user->id !== $this->user->id) {
            throw new InvalidArgumentException("User does not match, this context is supposed to be for the logged in user, expected user id: {$this->user?->id}, got user id: {$user->id}");
        }
    }

    public function getCurrentGroup(): ?Group
    {
        return $this->currentGroup;
    }

    public function isActingAsSystemAdmin(User $user): bool
    {
        $this->assertUserMatches($user);

        return $this->isActingAsSystemAdmin;
    }

    public function isActingAsDirectAdmin(User $user, Group $group): bool
    {
        $this->assertUserMatches($user);

        if (! $this->isActingAsGroupAdmin) {
            return false;
        }

        if (! $this->currentGroup) {
            return false;
        }

        if ($this->currentGroup->is($group)) {
            return true;
        }

        foreach ($group->ancestors() as $ancestor) {
            if ($this->isActingAsDirectAdmin($user, $ancestor)) {
                return true;
            }
        }

        return false;
    }

    public function actAsSystemAdmin()
    {
        $this->isActingAsSystemAdmin = true;
        $this->currentGroup = null;
        $this->isActingAsGroupAdmin = false;
    }

    public function actAsGroup(User $user, Group $group, bool $actsAsGroupAdmin = false): void
    {
        $this->assertUserMatches($user);
        $this->currentGroup = $group;
        $this->isActingAsGroupAdmin = $actsAsGroupAdmin;
        $this->isActingAsSystemAdmin = false;
        $this->user = $user;
    }

    public function isActingAsDirectMember(User $user, Group $group): bool
    {
        $this->assertUserMatches($user);

        return $this->getCurrentGroup() !== null && $this->getCurrentGroup()->is($group);
    }
}
