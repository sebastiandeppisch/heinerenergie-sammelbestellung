<?php

namespace App\Policies;

use App\Context\GroupContextContract;
use App\Models\AdviceStatus;
use App\Models\Group;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AdviceStatusPolicy
{
    use HandlesAuthorization;

    public function __construct(private GroupContextContract $groupContext) {}

    public function create(User $user, Group $group)
    {
        return $this->isGroupAdmin($user, $group);
    }

    public function update(User $user, AdviceStatus $advicestatus)
    {
        return $this->isGroupAdmin($user, $advicestatus->ownerGroup);
    }

    public function delete(User $user, AdviceStatus $advicestatus)
    {
        return $this->isGroupAdmin($user, $advicestatus->ownerGroup);
    }

    private function isGroupAdmin(User $user, Group $group)
    {
        if ($this->groupContext->actsAsSystemAdmin($user)) {
            return true;
        }

        if ($this->groupContext->actsAsGroupAdmin($user, $group)) {
            return true;
        }

        return false;
    }

    public function view(User $user, AdviceStatus $status)
    {
        $group = $status->ownerGroup;

        return $this->groupContext->hasAccessToGroup($user, $group);
    }
}
