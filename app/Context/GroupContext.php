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

        if (! $this->currentGroup) {
            return false;
        }

        if ($this->actsAsGroupAdmin && $this->currentGroup->is($group)) {
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
    }

    public function actAsGroup(User $user, Group $group, bool $actsAsGroupAdmin = false)
    {
        $this->currentGroup = $group;
        $this->actsAsGroupAdmin = $actsAsGroupAdmin;
        $this->user = $user;
    }

    public function hasAccessToGroup(User $user, Group $group): bool
    {
        $this->assertUserMatches($user);

        if ($this->actsAsSystemAdmin($user)) {
            return true;
        }

        // If we're in a specific group context, check if the target group is in our hierarchy
        if ($this->currentGroup) {
            // Check if target group is current group or descendant of current group
            return $group->is($this->currentGroup) ||
                   $group->ancestors()->contains($this->currentGroup);
        }

        // In global context, check if user is member of the group or any ancestor
        return $this->user->belongsToGroup($group) ||
               $group->ancestors()->contains(fn($ancestor) => $this->user->belongsToGroup($ancestor));
    }

    public function isAdmin(Group $group): bool
    {
        throw new \Exception('isAdmin is deprecated, use actsAsGroupAdmin instead');
        if (! $this->user) {
            return false;
        }

        if ($this->actsAsSystemAdmin) {
            return true;
        }

        // If we're in a specific group context and acting as admin
        if ($this->currentGroup && $this->actsAsGroupAdmin($this->user, $this->currentGroup)) {
            // Check if target group is current group or descendant of current group
            return $group->is($this->currentGroup) ||
                   $group->ancestors()->contains($this->currentGroup);
        }

        // In global context or not acting as admin, check if user is admin of the group or any ancestor
        $adminGroups = $this->user->groups()
            ->wherePivot('is_admin', true)
            ->get();

        // User is admin if they are admin of the target group or any of its ancestors
        return $adminGroups->contains(fn($adminGroup) => $adminGroup->is($group) ||
               $group->ancestors()->contains($adminGroup));
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

        return $userGroups->contains(fn($group) => $advisorGroups->contains($group));
    }
}