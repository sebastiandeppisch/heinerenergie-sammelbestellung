<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Advice;
use Illuminate\Support\Facades\Log;
use App\Context\GroupContextContract;
use Illuminate\Auth\Access\HandlesAuthorization;

class AdvicePolicy
{
    use HandlesAuthorization;

    public function __construct(private GroupContextContract $groupContext)
    {
    }

    public function viewAny(User $user)
    {
        return true;
    }

    public function view(User $user, Advice $advice)
    {
        if($this->groupContext->actsAsSystemAdmin($user)) {
            return true;
        }

        if($advice->advisor_id === $user->id) {
            return true;
        }

        $shared = $advice->shares()->where('advisor_id', $user->id)->first();

        if($shared) {
            return true;
        }
        return $this->groupContext->hasAccessToGroup($user, $advice->group);
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
