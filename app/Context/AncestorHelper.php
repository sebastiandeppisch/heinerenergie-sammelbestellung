<?php

namespace App\Context;

use App\Models\Group;
use App\Models\User;

trait AncestorHelper
{
    public function isActingAsAncestorAdmin(User $user, Group $group): bool
    {
        $group->loadMissing('parent');
        foreach ($group->ancestors() as $ancestor) {
            if ($this->isActingAsDirectAdmin($user, $ancestor)) {
                return true;
            }
        }

        return false;
    }

    public function isActingAsTransitiveAdmin(User $user, Group $group): bool
    {
        if ($this->isActingAsDirectAdmin($user, $group)) {
            return true;
        }

        if ($this->isActingAsAncestorAdmin($user, $group)) {
            return true;
        }

        return false;
    }

    public function isActingAsTransitiveMember(User $user, Group $group): bool
    {
        if ($this->isActingAsDirectMember($user, $group)) {
            return true;
        }

        if ($this->isActingAsAncestorMember($user, $group)) {
            return true;
        }

        return false;
    }

    public function isActingAsAncestorMember(User $user, Group $group): bool
    {
        foreach ($group->ancestors() as $ancestor) {
            if ($this->isActingAsDirectMember($user, $ancestor)) {
                return true;
            }
        }

        return false;
    }

    public function isActingAsDirectMemberOrAdmin(User $user, Group $group): bool
    {

        if ($this->isActingAsDirectAdmin($user, $group)) {
            return true;
        }

        if ($this->isActingAsDirectMember($user, $group)) {
            return true;
        }

        return false;
    }

    public function isActingAsTransitiveMemberOrAdmin(User $user, Group $group): bool
    {
        if ($this->isActingAsTransitiveAdmin($user, $group)) {
            return true;
        }

        if ($this->isActingAsTransitiveMember($user, $group)) {
            return true;
        }

        return false;
    }

    public function isActingAsAncestorMemberOrAdmin(User $user, Group $group): bool
    {
        if ($this->isActingAsAncestorAdmin($user, $group)) {
            return true;
        }

        if ($this->isActingAsAncestorMember($user, $group)) {
            return true;
        }

        return false;
    }
}
