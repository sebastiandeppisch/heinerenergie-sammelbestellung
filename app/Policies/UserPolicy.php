<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return true;
    }

    public function view(User $user, User $model)
    {
        return $user->isActingAsAdmin() || $model->id === $user->id;
    }

    public function create(User $user)
    {
        return $user->isActingAsAdmin();
    }

    public function update(User $user, User $model)
    {
        return $user->isActingAsAdmin();
    }

    public function delete(User $user, User $model)
    {
        //there is no defined behaviour for the foreign data advices & orders yet
        return false;
    }

    public function canActAsSystemAdmin(User $user)
    {
        return $user->is_admin;
    }
}
