<?php

namespace App\Policies;

use App\Models\Group;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class GroupPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // All users can view groups
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Group $group): bool
    {
        // Global admins can view all groups
        if ($user->isGlobalAdmin()) {
            return true;
        }

        // Users can view groups they belong to
        if ($user->belongsToGroup($group)) {
            return true;
        }

        // Group admins can view their groups and their hierarchy
        foreach ($user->administeredGroups()->get() as $administeredGroup) {
            if ($group->id === $administeredGroup->id || 
                $group->ancestors()->contains($administeredGroup) || 
                $group->descendants()->contains($administeredGroup)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, ?Group $parentGroup = null): bool
    {
        // Global admins can create groups anywhere
        if ($user->isGlobalAdmin()) {
            return true;
        }

        // If no parent group is specified, only global admins can create root groups
        if ($parentGroup === null) {
            return false;
        }

        // Group admins can only create subgroups under their administered groups
        return $user->isGroupAdmin($parentGroup);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Group $group): bool
    {
        // Global admins can update any group
        if ($user->isGlobalAdmin()) {
            return true;
        }

        // Group admins can update their group and subgroups
        return $user->isGroupAdmin($group);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Group $group): bool
    {
        // Only global admins can delete groups
        return $user->isGlobalAdmin();
    }

    /**
     * Determine whether the user can manage users of the group.
     */
    public function manageUsers(User $user, Group $group): bool
    {
        // Same rules as update
        return $this->update($user, $group);
    }

    /**
     * Determine whether the user can manage the group's area.
     */
    public function manageArea(User $user, Group $group): bool
    {
        // Same rules as update
        return $this->update($user, $group);
    }
}
