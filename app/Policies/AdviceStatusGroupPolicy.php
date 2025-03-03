<?php

namespace App\Policies;

use App\Context\GroupContextContract;
use App\Models\AdviceStatusGroup;
use App\Models\Group;
use App\Models\User;

class AdviceStatusGroupPolicy
{
    public function __construct(private GroupContextContract $groupContext) {}

    public function viewAny(User $user, Group $group): bool
    {
        return $this->groupContext->actsAsGroupAdmin($user, $group);
    }

    public function view(User $user, AdviceStatusGroup $groupAdviceStatus): bool
    {
        return $this->groupContext->actsAsGroupAdmin($user, $groupAdviceStatus->group);
    }

    public function create(User $user, Group $group): bool
    {
        return $this->groupContext->actsAsGroupAdmin($user, $group);
    }

    public function update(User $user, AdviceStatusGroup $groupAdviceStatus): bool
    {
        return $this->groupContext->actsAsGroupAdmin($user, $groupAdviceStatus->group);
    }
}
