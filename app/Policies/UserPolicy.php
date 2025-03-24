<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\GroupContextHelper;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    use GroupContextHelper;

    public function viewAny(User $user)
    {
        //anyone can see their colleagues
        return true;
    }

    public function view(User $user, User $model)
    {
        $groupsOfUser = $model->groups()->get();
        foreach($groupsOfUser as $group) {
            if ($this->groupContext->isActingAsTransitiveMemberOrAdmin($user, $group)) {
                return true;
            }
        }
        return false;
    }

    public function create(User $user)
    {
        //TODO this should be handled via group context
        return $user->groups()
            ->wherePivot('is_admin', true)
            ->exists();
    }

    public function update(User $user, User $model)
    {
        return $user->isActingAsAdmin();
    }

    public function delete(User $user, User $model)
    {
        //TODO there is no defined behaviour for the foreign data advices & orders yet
        return false;
    }

    public function canActAsSystemAdmin(User $user)
    {
        return $user->is_admin;
    }
}
