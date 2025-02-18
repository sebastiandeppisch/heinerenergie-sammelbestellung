<?php

namespace App\Policies;

use App\Models\Advice;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AdvicePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return true;
    }

    public function view(User $user, Advice $advice)
    {
        // Global admins can view any advice
        if ($user->isActingAsAdmin()) {
            return true;
        }

        // Get all groups the user belongs to
        $userGroups = $user->groups()->select('groups.id')->pluck('groups.id');

        // Check if the advice belongs to any of the user's groups
        return $userGroups->contains($advice->group_id);
    }

    public function viewDataProtected(User $user, Advice $advice)
    {
        return $this->view($user, $advice) || $advice->advisor_id === null;
    }

    public function create(User $user)
    {
        return true;
    }

    public function update(User $user, Advice $advice)
    {
        // Global admins can update any advice
        if ($user->isActingAsAdmin()) {
            return true;
        }

        // Get the user's role in the advice's group
        $userGroup = $user->groups()
            ->select('groups.id', 'group_user.is_admin')
            ->where('groups.id', $advice->group_id)
            ->first();

        // If user is an admin in this group, they can update
        if ($userGroup && $userGroup->pivot->is_admin) {
            return true;
        }

        // Advisors can only update their own advices
        return $advice->advisor_id === $user->id;
    }

    public function delete(User $user, Advice $advice)
    {
        //a advice can not be deleted, only set to done
        return false;
    }

    public function addAdvisors(User $user, Advice $advice)
    {
        return $this->update($user, $advice);
    }
}
