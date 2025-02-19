<?php

namespace App\Context;

use App\Models\User;
use App\Models\Group;
use App\Models\Advice;

class NoGroupContext implements GroupContextContract
{

    public function getCurrentGroup(): ?Group
    {
        return null;
    }

    public function actsAsSystemAdmin(User $user): bool{
        return false;
    }

    public function actsAsGroupAdmin(User $user, Group $group): bool
    {
        return false;
    }

    public function hasAccessToGroup(User $user, Group $group): bool
    {
        return false;
    }

    public function hasAccessToAdvice(User $user, Advice $advice): bool
    {
        return false;
    }

    public function hasAccessToAdvisor(User $user, User $advisor): bool
    {
        return false;
    }
}