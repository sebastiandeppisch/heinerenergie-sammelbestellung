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
        return $this->permission($user, $advice);
    }

    public function create(User $user)
    {
        return true;
    }

    public function update(User $user, Advice $advice)
    {
        return $this->permission($user, $advice);
    }


    public function delete(User $user, Advice $advice)
    {
        //a advice can not be deleted, only set to done
        return false;
    }

    public function addAdvisors(User $user, Advice $advice)
    {
        return $this->permission($user, $advice);
    }

    private function permission(User $user, Advice $advice)
    {
        return $user->is_admin || $advice->advisor_id === $user->id || $advice->shares->contains($user->id);
    }
}
