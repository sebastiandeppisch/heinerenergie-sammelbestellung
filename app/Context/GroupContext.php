<?php

namespace App\Context;

use App\Models\Advice;
use App\Models\Group;
use App\Models\User;
use InvalidArgumentException;

class GroupContext implements GroupContextContract
{
    public function __construct(
        private ?Group $currentGroup,
        private bool $actsAsSystemAdmin,
        private ?User $user,
        private bool $actsAsGroupAdmin = false
    ) {}

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

    public function actsAsSystemAdmin(User $user): bool
    {
        $this->assertUserMatches($user);

        return $this->actsAsSystemAdmin;
    }

    public function actsAsGroupAdmin(User $user, Group $group): bool
    {
        $this->assertUserMatches($user);

        if ($this->actsAsSystemAdmin($user)) {
            return true;
        }

        if (! $this->actsAsGroupAdmin) {
            return false;
        }

        if (! $this->currentGroup) {
            return false;
        }

        if ($this->currentGroup->is($group)) {
            return true;
        }

        foreach ($group->ancestors() as $ancestor) {
            if ($this->actsAsGroupAdmin($user, $ancestor)) {
                return true;
            }
        }

        return false;
    }

    public function actAsSystemAdmin()
    {
        $this->actsAsSystemAdmin = true;
        $this->currentGroup = null;
        $this->actsAsGroupAdmin = false;
    }

    public function actAsGroup(User $user, Group $group, bool $actsAsGroupAdmin = false): void
    {
        $this->assertUserMatches($user);
        $this->currentGroup = $group;
        $this->actsAsGroupAdmin = $actsAsGroupAdmin;
        $this->actsAsSystemAdmin = false;
        $this->user = $user;
    }

    public function hasAccessToGroup(User $user, Group $group): bool
    {
        $this->assertUserMatches($user);

        if ($this->actsAsSystemAdmin($user)) {
            return true;
        }

        if ($this->actsAsGroupAdmin($user, $group)) {
            return true;
        }

        if ($this->currentGroup) {
            if ($group->is($this->currentGroup)) {
                return true;
            }

            if ($group->ancestors()->contains($this->currentGroup)) {
                return true;
            }

            return $group->ancestors()->where('id', $this->currentGroup->id)->count() > 0 &&
                   $this->currentGroup->users()->where('user_id', $user->id)->exists();
        }

        if ($this->user->belongsToGroup($group)) {
            return true;
        }

        foreach ($group->ancestors() as $ancestor) {
            if ($this->user->belongsToGroup($ancestor)) {
                return true;
            }
        }

        return false;
    }

    public function hasAccessToAdvice(User $user, Advice $advice): bool
    {
        $this->assertUserMatches($user);

        if ($this->actsAsSystemAdmin($user)) {
            return true;
        }

        // Load the group relationship if not loaded
        if (! $advice->relationLoaded('group')) {
            $advice->load('group');
        }

        // TODO this should be changed, it is currently undefined behavior
        if ($advice->user_id === $user->id) {
            return true;
        }

        $group = $advice->group;

        // If advice has no group, deny access
        if (! $group) {
            return false;
        }

        return $this->hasAccessToGroup($user, $group);
    }

    public function hasAccessToAdvisor(User $user, User $advisor): bool
    {
        $this->assertUserMatches($user);

        if ($this->actsAsSystemAdmin($user)) {
            return true;
        }

        // If we have a current group context, check if advisor belongs to this group
        if ($this->currentGroup) {
            return $advisor->belongsToGroup($this->currentGroup);
        }

        // Without group context, check if user and advisor share any groups
        $userGroups = $this->user->groups()->get();
        $advisorGroups = $advisor->groups()->get();

        return $userGroups->contains(fn ($group) => $advisorGroups->contains($group));
    }
}
