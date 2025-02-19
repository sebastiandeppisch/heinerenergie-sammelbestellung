<?php

namespace App\Context;

use App\Models\Advice;
use App\Models\Group;
use App\Models\User;

interface GroupContextContract
{
    public function getCurrentGroup(): ?Group;

    public function actsAsSystemAdmin(User $user): bool;

    public function actsAsGroupAdmin(User $user, Group $group): bool;

    public function hasAccessToGroup(User $user, Group $group): bool;

    public function hasAccessToAdvice(User $user, Advice $advice): bool;

    public function hasAccessToAdvisor(User $user, User $advisor): bool;
}
