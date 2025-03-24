<?php

namespace App\Context;

use App\Models\Advice;
use App\Models\Group;
use App\Models\User;

class GlobalGroupContext implements GroupContextContract
{
    use AncestorHelper;

    public function getCurrentGroup(): ?Group
    {
        return null;
    }

    public function isActingAsSystemAdmin(User $user): bool
    {
        return $user->isGlobalAdmin();
    }

    public function isActingAsDirectAdmin(User $user, Group $group): bool
    {
        if ($group->users()->wherePivot('user_id', $user->id)->wherePivot('is_admin', true)->exists()) {
            return true;
        }
        return false;
    }

    public function isActingAsDirectMember(User $user, Group $group): bool
    {
        return $group->users->contains($user);
    }
}
