<?php

namespace App\Policies;

use App\Context\GroupContextContract;
use App\Models\Advice;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AdvicePolicy
{
    use HandlesAuthorization;

    public function __construct(private GroupContextContract $groupContext) {}

    public function viewAny(User $user)
    {
        return true;
    }

    public function view(User $user, Advice $advice)
    {
        if ($this->groupContext->actsAsSystemAdmin($user)) {
            return true;
        }

        if ($advice->advisor_id === $user->id) {
            return true;
        }

        $shared = $advice->shares()->where('advisor_id', $user->id)->first();

        if ($shared) {
            return true;
        }

        return $this->groupContext->hasAccessToGroup($user, $advice->group);
    }

    public function viewDataProtected(User $user, Advice $advice)
    {

        if ($this->groupContext->hasAccessToGroup($user, $advice->group)) {
            if ($advice->advisor_id === null) {
                return true;
            }
        }

        return $this->view($user, $advice);
    }

    public function create(User $user)
    {
        return true;
    }

    public function update(User $user, Advice $advice)
    {
        if ($this->groupContext->actsAsSystemAdmin($user)) {
            return true;
        }

        return $this->groupContext->actsAsTransitiveGroupMember($user, $advice->group);
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
}
