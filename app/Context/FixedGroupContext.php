<?php

namespace App\Context;

use App\Models\Group;
use App\Models\User;

class FixedGroupContext implements GroupContextContract
{
    use AncestorHelper;

    public function __construct(private Group $group) {}

    public function getCurrentGroup(): ?Group
    {
        return $this->group;
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
