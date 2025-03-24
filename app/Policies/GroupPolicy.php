<?php

namespace App\Policies;

use App\Context\GroupContextContract;
use App\Models\Group;
use App\Models\User;
use App\Policies\Concerns\GroupContextHelper;
use Illuminate\Auth\Access\HandlesAuthorization;

class GroupPolicy
{
    use HandlesAuthorization;

    use GroupContextHelper;

    public function viewAny(User $user): bool
    {
        // All users can view groups
        return true;
    }

    public function view(User $user, Group $group): bool
    {
        if ($this->groupContext->isActingAsTransitiveMemberOrAdmin($user, $group)) {
            return true;
        }

        foreach($group->descendants() as $descendant) {
            if ($this->groupContext->isActingAsTransitiveMemberOrAdmin($user, $descendant)) {
                return true;
            }
        }
        return false;
    }

    public function create(User $user, ?Group $parentGroup = null): bool
    {
        // If no parent group is specified, only global admins can create root groups
        if ($parentGroup === null) {
            return false;
        }

        return $this->groupContext->isActingAsTransitiveAdmin($user, $parentGroup);
    }

    public function createAny(User $user): bool
    {
        if ($this->groupContext->isActingAsSystemAdmin($user)) {
            return true;
        }

        //users that are admins of some group can create groups, even if they dont act as group admin
        //TODO this should be handled via group context
        return $user->groups()
            ->wherePivot('is_admin', true)
            ->exists();
    }

    public function update(User $user, Group $group): bool
    {
        if ($this->groupContext->isActingAsTransitiveAdmin($user, $group)) {
            return true;
        }

        return false;
    }

    public function delete(User $user, Group $group): bool
    {
        if($group->advices()->exists()) {
            return false;
        }
        return $this->groupContext->isActingAsTransitiveAdmin($user, $group);
    }

    public function manageUsers(User $user, Group $group): bool
    {
        return $this->update($user, $group);
    }

    public function manageArea(User $user, Group $group): bool
    {
        // Same rules as update
        return $this->update($user, $group);
    }

    public function actAsGroupAdmin(User $user, Group $group): bool
    {
        if ($user->isGlobalAdmin()) {
            return true;
        }

        return $group->users()->where('user_id', $user->id)->where('group_user.is_admin', true)->exists();
    }

    public function actAsGroup(User $user, Group $group): bool
    {
        if ($user->isGlobalAdmin()) {
            return true;
        }

        return $user->belongsToGroup($group);
    }
}
