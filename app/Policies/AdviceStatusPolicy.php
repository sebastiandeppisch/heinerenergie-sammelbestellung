<?php

namespace App\Policies;

use App\Models\AdviceStatus;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AdviceStatusPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return true;

    }

    public function view(User $user, AdviceStatus $adviceStatus)
    {
        return true;
    }

    public function create(User $user)
    {
        return $user->isActingAsAdmin();
    }

    public function update(User $user, AdviceStatus $adviceStatus)
    {
        return $user->isActingAsAdmin();
    }

    public function delete(User $user, AdviceStatus $adviceStatus)
    {
        return $user->isActingAsAdmin();
    }
}
