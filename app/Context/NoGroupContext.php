<?php

namespace App\Context;

use App\Models\Group;
use App\Models\User;

class NoGroupContext implements GroupContextContract
{
    use AncestorHelper;

    public function getCurrentGroup(): ?Group
    {
        return null;
    }

    public function isActingAsSystemAdmin(User $user): bool
    {
        return false;
    }

    public function isActingAsDirectAdmin(User $user, Group $group): bool
    {
        return false;
    }

    public function isActingAsDirectMember(User $user, Group $group): bool
    {
        return false;
    }

}
