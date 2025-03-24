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

    public function actsAsTransitiveGroupAdmin(User $user, Group $group): bool;

    //public function hasAccessToGroup(User $user, Group $group): bool;

    public function actsAsGroupMember(User $user, Group $group): bool;

    public function actsAsTransitiveGroupMember(User $user, Group $group): bool;

    //public function hasAccessToAdvice(User $user, Advice $advice): bool;

    //public function hasAccessToAdvisor(User $user, User $advisor): bool;
}
