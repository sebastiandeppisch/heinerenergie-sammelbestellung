<?php

namespace App\Policies;

use App\Models\AdviceStatus;
use App\Models\Group;
use App\Models\User;
use App\Policies\Concerns\GroupContextHelper;
use Illuminate\Auth\Access\HandlesAuthorization;

class AdviceStatusPolicy
{
    use GroupContextHelper;
    use HandlesAuthorization;

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
        if ($this->groupContext->isActingAsSystemAdmin($user)) {
            return true;
        }

        if ($this->groupContext->isActingAsDirectAdmin($user, $group)) {
            return true;
        }

        return false;
    }

    public function view(User $user, AdviceStatus $status)
{
        $status->loadMissing('ownerGroup');

        $group = $status->ownerGroup;

        if ($this->groupContext->isActingAsTransitiveMemberOrAdmin($user, $group)) {
            return true;
        }

        if ($group->parentGroups === null) {
            return false;
        }

        foreach ($group->parentGroups as $parentGroup) {
            if ($this->groupContext->isActingAsTransitiveMemberOrAdmin($user, $parentGroup)) {
                return true;
            }
        }

        return false;
    }
}
