<?php

namespace App\Policies;

use App\Context\GroupContextContract;
use App\Models\Group;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class GroupPolicy
{
    use HandlesAuthorization;

    public function __construct(private GroupContextContract $groupContext) {}

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
        if ($this->groupContext->actsAsSystemAdmin($user)) {
            return true;
        }

        if ($this->groupContext->hasAccessToGroup($user, $group)) {
            return true;
        }

        if ($this->groupContext->actsAsGroupAdmin($user, $group)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, ?Group $parentGroup = null): bool
    {
        // Global admins can create groups anywhere
        if ($this->groupContext->actsAsSystemAdmin($user)) {
            return true;
        }

        // If no parent group is specified, only global admins can create root groups
        if ($parentGroup === null) {
            return false;
        }

        return $this->groupContext->actsAsGroupAdmin($user, $parentGroup);
    }

    public function createAny(User $user): bool
    {
        if ($this->groupContext->actsAsSystemAdmin($user)) {
            return true;
        }

        return $user->administeredGroups()
            ->where(function ($query) use ($user) {
                $query->whereIn('id', function ($subQuery) use ($user) {
                    $subQuery->select('id')
                        ->from('groups')
                        ->whereExists(function ($exists) use ($user) {
                            $exists->select('id')
                                ->from('group_user')
                                ->whereColumn('group_id', 'groups.id')
                                ->where('user_id', $user->id)
                                ->where('is_admin', true);
                        });
                });
            })
            ->exists();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Group $group): bool
    {
        if ($this->groupContext->actsAsSystemAdmin($user)) {
            return true;
        }

        // Check if user is acting as group admin and has access to the group
        if ($this->groupContext->actsAsGroupAdmin($user, $group) && $this->groupContext->hasAccessToGroup($user, $group)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Group $group): bool
    {
        if ($this->groupContext->actsAsSystemAdmin($user)) {
            return true;
        }

        return $this->groupContext->actsAsGroupAdmin($user, $group);
    }

    /**
     * Determine whether the user can manage users of the group.
     */
    public function manageUsers(User $user, Group $group): bool
    {
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
