<?php

namespace App\Policies;

use App\Models\Advice;
use App\Models\User;
use App\Policies\Concerns\GroupContextHelper;
use Illuminate\Auth\Access\HandlesAuthorization;

class AdvicePolicy
{
    use GroupContextHelper;
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return true;
    }

    public function view(User $user, Advice $advice)
    {
        if ($advice->advisor_id === $user->id) {
            return true;
        }

        $shared = $advice->shares()->where('advisor_id', $user->id)->first();

        if ($shared) {
            return true;
        }

        return $this->groupContext->isActingAsTransitiveMemberOrAdmin($user, $advice->group);
    }

    public function viewDataProtected(User $user, Advice $advice)
    {
        if ($advice->advisor_id === null) {
            if ($this->groupContext->isActingAsTransitiveMemberOrAdmin($user, $advice->group)) {
                return true;
            }
        }

        return $this->view($user, $advice);
    }

    public function create(User $user)
    {
        // everyone, even guests can create advices
        return true;
    }

    public function update(User $user, Advice $advice)
    {
        if ($advice->advisor_id === $user->id) {
            return true;
        }

        $shared = $advice->shares()->where('advisor_id', $user->id)->first();

        if ($shared) {
            return true;
        }

        if ($this->groupContext->isActingAsTransitiveAdmin($user, $advice->group)) {
            return true;
        }

        return false;
    }

    public function delete(User $user, Advice $advice)
    {
        // a advice can not be deleted, only set to done
        return false;
    }

    public function addAdvisors(User $user, Advice $advice)
    {
        return $this->update($user, $advice);
    }

    public function transfer(User $user, Advice $advice)
    {
        return $this->update($user, $advice);
    }

    public function storeComment(User $user, Advice $advice)
    {
        return $this->update($user, $advice);
    }

    public function unassign(User $user, Advice $advice)
    {
        return $this->update($user, $advice);
    }
}
